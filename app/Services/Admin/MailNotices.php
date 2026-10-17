<?php

namespace App\Services\Admin;

use App\Jobs\SendNoticeRecipient;
use App\Models\MailNoticeCampaign;
use App\Models\MailNoticeRecipient;
use App\Models\User;
use App\Services\Shared\PublicContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class MailNotices
{
    public const STATUSES = ['queued', 'sending', 'sent', 'failed', 'unconfirmed'];

    public function installed(): bool
    {
        return Schema::hasTable('mail_notice_templates') && Schema::hasTable('mail_notice_campaigns') && Schema::hasTable('mail_notice_recipients');
    }

    private function realMailer(string $name, array $visited = []): bool
    {
        if (in_array($name, $visited, true)) {
            return false;
        }
        $mailer = config('mail.mailers.'.$name, []);
        $transport = $mailer['transport'] ?? '';
        if (in_array($transport, ['failover', 'roundrobin'], true)) {
            $children = $mailer['mailers'] ?? [];

            return count($children) > 0 && collect($children)->every(fn ($child) => $this->realMailer($child, [...$visited, $name]));
        }

        return in_array($transport, ['smtp', 'sendmail', 'ses', 'ses-v2', 'mailgun', 'postmark', 'resend'], true);
    }

    public function ready(?string $mailer = null): bool
    {
        return $this->installed() && Schema::hasTable('jobs')
            && ! in_array(config('queue.connections.'.config('queue.default').'.driver'), [null, 'sync', 'null'], true)
            && $this->realMailer($mailer ?? config('mail.default'))
            && filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL);
    }

    public function eligible(): Builder
    {
        return User::query()->where('status', 1)->whereNotNull('email_verified_at');
    }

    public function validEmail(?string $email): bool
    {
        return $email !== null && $email === trim($email) && strlen($email) <= 255 && (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function body(string $html): string
    {
        $body = app(PublicContent::class)->html($html);
        if (preg_replace('/[\s\p{Z}\x{200B}]+/u', '', html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8')) === '') {
            throw ValidationException::withMessages(['body' => 'Write a message before continuing.']);
        }

        return $body;
    }

    public function counts(int $campaign): array
    {
        $counts = array_fill_keys(self::STATUSES, 0);
        foreach (MailNoticeRecipient::where('campaign_id', $campaign)->selectRaw('status, COUNT(*) AS total')->groupBy('status')->get() as $row) {
            $counts[$row->status] = (int) $row->total;
        }

        return [...$counts, 'total' => array_sum($counts)];
    }

    public function create(User $sender, array $data): MailNoticeCampaign
    {
        $body = $this->body($data['body']);
        $ids = array_values(array_unique($data['user_ids'] ?? []));
        sort($ids);
        $fingerprint = hash('sha256', json_encode([$data['subject'], $body, $data['audience'], $data['audience'] === 'selected' ? $ids : []]));
        $existing = MailNoticeCampaign::where('submission_key', $data['submission_key'])->first();
        if ($existing) {
            abort_unless($existing->created_by === $sender->id, 403);
            if ($existing->request_hash !== $fingerprint) {
                throw ValidationException::withMessages(['submission_key' => 'This submission was already queued with different content. Check Send history before creating another notice.']);
            }

            return $existing;
        }
        if (! $this->ready()) {
            throw ValidationException::withMessages(['sending' => 'Configure a real mail transport and an asynchronous queue before sending.']);
        }
        if ($data['audience'] === 'selected') {
            $users = $this->eligible()->whereIn('id', $ids)->get(['id', 'name', 'email']);
            if (! count($ids) || $users->count() !== count($ids) || $users->contains(fn ($user) => ! $this->validEmail($user->email))) {
                throw ValidationException::withMessages(['user_ids' => 'Choose active users with verified, valid email addresses. Refresh the recipient list if an account has changed.']);
            }
        }

        return DB::transaction(function () use ($sender, $data, $body, $ids, $fingerprint) {
            // Lock the actor so simultaneous submissions with the same key cannot create duplicate campaigns.
            User::whereKey($sender->id)->lockForUpdate()->firstOrFail();
            $existing = MailNoticeCampaign::where('submission_key', $data['submission_key'])->first();
            if ($existing) {
                abort_unless($existing->created_by === $sender->id, 403);
                if ($existing->request_hash !== $fingerprint) {
                    throw ValidationException::withMessages(['submission_key' => 'This submission was already queued with different content. Check Send history.']);
                }

                return $existing;
            }
            $campaign = MailNoticeCampaign::create(['created_by' => $sender->id, 'submission_key' => $data['submission_key'], 'request_hash' => $fingerprint,
                'template_id' => $data['template_id'] ?? null, 'subject' => $data['subject'], 'body' => $body,
                'audience' => $data['audience'], 'mailer' => config('mail.default'), 'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name') ?: 'iTechOrbit']);
            $query = $this->eligible();
            if ($data['audience'] === 'selected') {
                $query->whereIn('id', $ids);
            }
            $selectedCount = 0;
            $query->select(['id', 'name', 'email'])->chunkById(500, function ($users) use ($campaign, &$selectedCount) {
                $rows = [];
                foreach ($users as $user) {
                    if (! $this->validEmail($user->email)) {
                        continue;
                    }
                    $selectedCount++;
                    $rows[] = ['campaign_id' => $campaign->id, 'user_id' => $user->id, 'name' => $user->name,
                        'email' => strtolower($user->email), 'status' => 'queued', 'created_at' => now(), 'updated_at' => now()];
                }
                // A case-normalized unique email prevents one person receiving multiple copies.
                DB::table('mail_notice_recipients')->insertOrIgnore($rows);
            });
            if ($data['audience'] === 'selected' && $selectedCount !== count($ids)) {
                throw ValidationException::withMessages(['user_ids' => 'Some selected accounts are no longer eligible. Refresh the recipient list.']);
            }
            if (! MailNoticeRecipient::where('campaign_id', $campaign->id)->exists()) {
                throw ValidationException::withMessages(['user_ids' => 'There are no eligible recipients to send to.']);
            }

            return $campaign;
        });
    }

    public function dispatchPending(int $limit = 500, ?int $campaign = null): int
    {
        $this->expireInterrupted();
        if (! $this->ready()) {
            return 0;
        }
        $ids = MailNoticeRecipient::where('status', 'queued')->whereNull('dispatched_at')
            ->when($campaign, fn ($query) => $query->where('campaign_id', $campaign))->orderBy('id')->limit($limit)->pluck('id');
        $dispatched = 0;
        foreach ($ids as $id) {
            // The dedicated database queue uses this same DB connection. Enqueue and outbox acknowledgement are atomic.
            DB::transaction(function () use ($id, &$dispatched) {
                $recipient = MailNoticeRecipient::whereKey($id)->where('status', 'queued')->whereNull('dispatched_at')->lockForUpdate()->first();
                if (! $recipient) {
                    return;
                }
                Queue::connection('mail_notices')->push(new SendNoticeRecipient($recipient->id, $recipient->generation), '', 'mail-notices');
                $recipient->update(['dispatched_at' => now()]);
                $dispatched++;
            });
        }

        return $dispatched;
    }

    public function expireInterrupted(): void
    {
        if ($this->installed()) {
            MailNoticeRecipient::where('status', 'sending')->where('sending_at', '<', now()->subMinutes(5))
                ->update(['status' => 'unconfirmed', 'last_error' => 'The worker stopped before confirming the send. Review provider logs before taking further action.', 'updated_at' => now()]);
        }
    }
}
