<?php

namespace App\Jobs;

use App\Mail\SendMailNotice;
use App\Models\MailNoticeCampaign;
use App\Models\MailNoticeRecipient;
use App\Services\MailNotices;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class SendNoticeRecipient implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 0;

    public int $maxExceptions = 3;

    public int $timeout = 60;

    public bool $failOnTimeout = true;

    public array $backoff = [60, 300];

    public function __construct(public int $recipientId, public int $generation) {}

    public function middleware(): array
    {
        return [new RateLimited('mail-notices')];
    }

    public function handle(MailNotices $notices): void
    {
        $recipient = MailNoticeRecipient::find($this->recipientId);
        if (! $recipient || $recipient->generation !== $this->generation || $recipient->status !== 'queued') {
            return;
        }
        $campaign = MailNoticeCampaign::findOrFail($recipient->campaign_id);
        if (! $notices->ready($campaign->mailer)) {
            $recipient->update(['status' => 'failed', 'last_error' => 'Sending is unavailable. Configure the mail transport and asynchronous queue, then retry.']);

            return;
        }
        $claimed = MailNoticeRecipient::whereKey($recipient->id)->where('generation', $this->generation)->where('status', 'queued')
            ->update(['status' => 'sending', 'sending_at' => now(), 'attempts' => DB::raw('attempts + 1'), 'updated_at' => now()]);
        if (! $claimed) {
            return;
        }
        $recipient->refresh();
        try {
            $sent = Mail::mailer($campaign->mailer)->to($recipient->email, $recipient->name)->send(new SendMailNotice($campaign, $recipient));
            if ($sent === null) {
                $recipient->update(['status' => 'unconfirmed', 'last_error' => 'The transport did not return an acceptance confirmation. Review provider logs.']);

                return;
            }
        } catch (Throwable $exception) {
            $code = (int) $exception->getCode();
            // Explicit rejection is safe to retry; a timeout or unknown outcome could already have delivered the email.
            if ($code >= 400 && $code <= 599) {
                $recipient->update(['status' => $recipient->attempts >= 3 ? 'failed' : 'queued', 'last_error' => 'The mail server rejected this message.']);
                throw new RuntimeException('Mail notice rejected by transport.', $code);
            }
            $recipient->update(['status' => 'unconfirmed', 'last_error' => 'The mail transport did not confirm acceptance. Check provider logs before resending.']);

            return;
        }
        $recipient->update(['status' => 'sent', 'sent_at' => now(), 'last_error' => null]);
    }

    public function failed(?Throwable $exception): void
    {
        $recipient = MailNoticeRecipient::whereKey($this->recipientId)->where('generation', $this->generation)->first();
        if ($recipient && in_array($recipient->status, ['queued', 'sending'], true)) {
            $recipient->update(['status' => $recipient->status === 'sending' ? 'unconfirmed' : 'failed',
                'last_error' => $recipient->status === 'sending' ? 'The worker stopped before confirmation. Review provider logs.' : 'The queued send failed. Retry after checking the mail configuration.']);
        }
    }
}
