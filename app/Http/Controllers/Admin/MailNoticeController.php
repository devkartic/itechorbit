<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Role;
use App\Models\MailNoticeCampaign;
use App\Models\MailNoticeRecipient;
use App\Models\MailNoticeTemplate;
use App\Services\Admin\AdminPresentation;
use App\Services\Admin\MailNotices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Throwable;

class MailNoticeController extends Controller
{
    public function __construct(private MailNotices $notices)
    {
        $this->middleware(['auth', 'verified']);
        $this->middleware(function ($request, $next) {
            abort_unless($request->user()->status && in_array((int) $request->user()->role_id, [Role::$default_admin, Role::$admin], true), 403);

            return $next($request);
        });
    }

    private function installed(): void
    {
        abort_unless($this->notices->installed(), 409, 'Apply the email notices migration first.');
    }

    private function eligibleCount(): int
    {
        return $this->notices->eligible()->select('email')->cursor()->filter(fn ($user) => $this->notices->validEmail($user->email))->unique(fn ($user) => strtolower($user->email))->count();
    }

    public function index()
    {
        return redirect()->route('workspace.email-notices.compose');
    }

    private function page(string $section, array $extra = [])
    {
        $installed = $this->notices->installed();

        return Inertia::render('Admin/EmailNotices/'.ucfirst($section), ['navigation' => app(AdminPresentation::class)->navigation(), 'section' => $section,
            'installed' => $installed, 'sendingReady' => $installed && $this->notices->ready(),
            'eligibleCount' => $installed && $section === 'compose' ? $this->eligibleCount() : 0, ...$extra]);
    }

    public function compose(Request $request)
    {
        $data = $request->validate(['template' => ['nullable', 'integer', 'min:1']]);
        $template = isset($data['template']) && $this->notices->installed()
            ? MailNoticeTemplate::findOrFail($data['template'])->only(['id', 'name', 'subject', 'body']) : null;

        return $this->page('compose', ['initialTemplate' => $template]);
    }

    public function templatePage()
    {
        return $this->page('templates');
    }

    public function history(Request $request)
    {
        $data = $request->validate(['campaign' => ['nullable', 'integer', 'min:1']]);
        $campaign = isset($data['campaign']) && $this->notices->installed()
            ? MailNoticeCampaign::findOrFail($data['campaign'])->id : null;

        return $this->page('history', ['selectedCampaignId' => $campaign]);
    }

    public function recipients(Request $request)
    {
        $this->installed();
        $data = $request->validate(['key' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1']]);
        $query = $this->notices->eligible();
        if ($key = trim($data['key'] ?? '')) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$key.'%')->orWhere('email', 'like', '%'.$key.'%'));
        }
        $page = (int) ($data['page'] ?? 1);
        $total = 0;
        $rows = [];
        foreach ($query->orderBy('name')->orderBy('id')->select(['id', 'name', 'email'])->cursor() as $user) {
            if (! $this->notices->validEmail($user->email)) {
                continue;
            }
            if ($total >= ($page - 1) * 20 && $total < $page * 20) {
                $rows[] = $user->only(['id', 'name', 'email']);
            }
            $total++;
        }

        return response()->json(['data' => $rows, 'total' => $total, 'current_page' => $page, 'last_page' => max(1, (int) ceil($total / 20))]);
    }

    public function templates(Request $request)
    {
        $this->installed();
        $data = $request->validate(['key' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1']]);

        return response()->json(MailNoticeTemplate::when($data['key'] ?? '', fn ($query, $key) => $query->where('name', 'like', '%'.$key.'%'))
            ->latest('id')->paginate(20, ['id', 'name', 'subject', 'updated_at']));
    }

    public function template(MailNoticeTemplate $template)
    {
        $this->installed();

        return response()->json($template->only(['id', 'name', 'subject', 'body']));
    }

    private function message(Request $request, bool $template = false): array
    {
        $data = $request->validate(['name' => $template ? ['required', 'string', 'max:100'] : ['sometimes', 'string', 'max:100'],
            'subject' => ['required', 'string', 'max:255', 'not_regex:/[\r\n]/'], 'body' => ['required', 'string', 'max:1000000']]);
        $data['subject'] = trim($data['subject']);
        $data['body'] = $this->notices->body($data['body']);

        return $data;
    }

    public function saveTemplate(Request $request, ?MailNoticeTemplate $template = null)
    {
        $this->installed();
        $data = $this->message($request, true);
        $template ??= new MailNoticeTemplate;
        $template->fill($data)->save();

        return response()->json(['success' => 'Template saved.', 'template' => $template->only(['id', 'name', 'subject', 'body'])]);
    }

    public function deleteTemplate(MailNoticeTemplate $template)
    {
        $this->installed();
        $template->delete();

        return response()->json(['success' => 'Template deleted. Existing sends keep their saved message.']);
    }

    public function preview(Request $request)
    {
        $this->installed();
        $data = $this->message($request);
        $campaign = new MailNoticeCampaign([...$data, 'from_name' => config('mail.from.name') ?: 'iTechOrbit']);
        $recipient = new MailNoticeRecipient(['name' => '']);

        return response()->json(['html' => view('mail.notice', compact('campaign', 'recipient'))->render()]);
    }

    public function send(Request $request)
    {
        $this->installed();
        $message = $this->message($request);
        $data = $request->validate(['submission_key' => ['required', 'uuid'], 'audience' => ['required', Rule::in(['all', 'selected'])],
            'template_id' => ['nullable', 'integer', 'exists:mail_notice_templates,id'],
            'user_ids' => ['required_if:audience,selected', 'array'], 'user_ids.*' => ['integer', 'distinct', 'exists:users,id']]);
        $campaign = $this->notices->create($request->user(), [...$data, ...$message]);
        try {
            $this->notices->dispatchPending(500, $campaign->id);
        } catch (Throwable $exception) {
            // The durable outbox remains queued; the scheduled dispatcher will resume it.
        }

        return response()->json(['success' => 'Notice queued. Track each recipient in Send history.', 'campaign_id' => $campaign->id]);
    }

    public function campaigns(Request $request)
    {
        $this->installed();
        $request->validate(['page' => ['nullable', 'integer', 'min:1']]);
        $campaigns = MailNoticeCampaign::latest('id')->paginate(20, ['id', 'subject', 'audience', 'created_at']);
        $campaigns->getCollection()->transform(fn ($campaign) => [...$campaign->toArray(), 'counts' => $this->notices->counts($campaign->id)]);

        return response()->json($campaigns);
    }

    public function campaign(Request $request, MailNoticeCampaign $campaign)
    {
        $this->installed();
        $data = $request->validate(['page' => ['nullable', 'integer', 'min:1'], 'status' => ['nullable', Rule::in(MailNotices::STATUSES)]]);
        $recipients = MailNoticeRecipient::where('campaign_id', $campaign->id)->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('id')->paginate(20, ['id', 'name', 'email', 'status', 'attempts', 'last_error', 'sent_at']);

        return response()->json(['campaign' => $campaign->only(['id', 'subject', 'audience', 'created_at']), 'counts' => $this->notices->counts($campaign->id), 'recipients' => $recipients]);
    }

    public function retry(MailNoticeCampaign $campaign)
    {
        $this->installed();
        abort_unless($this->notices->ready($campaign->mailer), 422, 'Configure email sending before retrying.');
        DB::transaction(function () use ($campaign) {
            MailNoticeCampaign::whereKey($campaign->id)->lockForUpdate()->firstOrFail();
            MailNoticeRecipient::where('campaign_id', $campaign->id)->where('status', 'failed')
                ->update(['status' => 'queued', 'generation' => DB::raw('generation + 1'), 'attempts' => 0, 'dispatched_at' => null, 'sending_at' => null, 'last_error' => null, 'updated_at' => now()]);
        });
        try {
            $this->notices->dispatchPending(500, $campaign->id);
        } catch (Throwable $exception) {
            // Scheduled dispatch resumes durable pending recipients.
        }

        return response()->json(['success' => 'Failed recipients queued again. Successful and unconfirmed sends were not resent.']);
    }
}
