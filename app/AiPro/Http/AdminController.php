<?php

namespace App\AiPro\Http;

use App\AiPro\Jobs\SyncSite;
use App\AiPro\Knowledge;
use App\AiPro\LocalContent;
use App\AiPro\Models\Conversation;
use App\AiPro\Models\Message;
use App\AiPro\Models\Site;
use App\AiPro\Models\Source;
use App\AiPro\Models\SyncRun;
use App\AiPro\Providers;
use App\Http\Controllers\Controller;
use App\Services\Admin\AdminPresentation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
        $this->middleware(function ($request, $next) {
            abort_unless($request->user()->status && in_array((int) $request->user()->role_id, [1, 2], true), 403);

            return $next($request);
        });
    }

    public const SECTIONS = ['overview', 'websites', 'knowledge', 'synchronization', 'conversations', 'installation'];

    public function index(Request $request)
    {
        $query = $request->validate(['site' => ['nullable', 'integer', 'min:1']]);

        return redirect()->route('workspace.ai-pro.overview', array_filter($query));
    }

    public function page(Request $request, string $section)
    {
        abort_unless(in_array($section, self::SECTIONS, true), 404);
        $selection = $request->validate(['site' => ['nullable', 'integer', 'min:1'], 'page' => ['nullable', 'integer', 'min:1'],
            'conversation' => ['nullable', 'integer', 'min:1'], 'message_page' => ['nullable', 'integer', 'min:1']]);
        $installed = Schema::hasTable('ai_pro_sites');
        if ($installed && isset($selection['site'])) {
            Site::findOrFail($selection['site']);
        }

        return Inertia::render('Admin/AiPro/'.ucfirst($section), ['navigation' => app(AdminPresentation::class)->navigation(),
            'installed' => $installed, 'providerReady' => Providers::ready(), 'provider' => config('ai_pro.provider'),
            'embeddingProvider' => config('ai_pro.embedding_provider'), 'model' => config('ai_pro.model'),
            'embeddingModel' => config('ai_pro.embedding_model'), 'enabled' => (bool) config('ai_pro.enabled'),
            'backendUrl' => rtrim(config('app.url'), '/'), 'widgetVersion' => substr(hash_file('sha256', resource_path('ai-pro/widget.js')), 0, 12), 'selection' => $selection]);
    }

    private function installed(): void
    {
        abort_unless(Schema::hasTable('ai_pro_sites'), 409, 'Apply the iTechOrbit AI migration first.');
    }

    public function data(Request $request)
    {
        $this->installed();
        $input = $request->validate(['site' => ['nullable', 'integer', 'min:1'], 'page' => ['nullable', 'integer', 'min:1'],
            'section' => ['nullable', Rule::in(self::SECTIONS)]]);
        $sites = Site::orderBy('id')->get();
        $site = isset($input['site']) ? $sites->firstWhere('id', (int) $input['site']) : $sites->first();
        abort_if(isset($input['site']) && ! $site, 404);
        $section = $input['section'] ?? 'overview';
        $data = ['sites' => $sites, 'selected' => $site?->id];
        if ($site && $section === 'knowledge') {
            $data['sources'] = Source::where('site_id', $site->id)->latest('id')->paginate(20);
        }
        if ($site && $section === 'synchronization') {
            $data['runs'] = SyncRun::where('site_id', $site->id)->latest('id')->paginate(20);
        }
        if ($site && $section === 'conversations') {
            $data['conversations'] = Conversation::where('site_id', $site->id)->latest('id')->paginate(20, ['id', 'created_at', 'expires_at']);
        }
        if ($site && $section === 'overview') {
            $data['counts'] = ['sources' => Source::where('site_id', $site->id)->count(),
                'ready' => Source::where('site_id', $site->id)->where('status', 'ready')->count(),
                'conversations' => Conversation::where('site_id', $site->id)->count(),
                'questionsToday' => Message::where('site_id', $site->id)->where('created_at', '>=', now()->startOfDay())->count()];
            $data['recentRuns'] = SyncRun::where('site_id', $site->id)->latest('id')->limit(3)->get();
        }

        return response()->json($data);
    }

    public function saveSite(Request $request, ?Site $site = null)
    {
        $this->installed();
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'url' => ['required', 'url:http,https', 'max:2048'],
            'origins' => ['required', 'array', 'min:1', 'max:10'], 'origins.*' => ['required', 'url:http,https', 'max:255', 'distinct'],
            'enabled' => ['required', 'boolean'], 'welcome' => ['required', 'string', 'max:255'], 'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'contact_url' => ['nullable', 'url:http,https', 'max:2048'], 'minute_limit' => ['required', 'integer', 'min:1', 'max:60'], 'daily_limit' => ['required', 'integer', 'min:1', 'max:10000']]);
        foreach ($data['origins'] as $origin) {
            abort_unless(preg_match('#^https?://[a-zA-Z0-9.-]+(?::[0-9]+)?$#D', $origin), 422, 'Allowed origins must contain only scheme, hostname and optional port.');
        }
        abort_unless($this->origin($data['url']) && in_array($this->origin($data['url']), $data['origins'], true), 422, 'Include the website URL origin in allowed origins.');
        if ($data['contact_url']) {
            abort_unless($this->origin($data['contact_url']) === $this->origin($data['url']), 422, 'Contact URL must belong to this website.');
        }
        if ($site?->exists) {
            abort_unless($this->origin($site->url) === $this->origin($data['url']), 422, 'Create a new website to change its origin.');
            $site->update($data);
        } else {
            abort_if(Site::count() >= 50, 422, 'Website limit reached.');
            $site = Site::create([...$data, 'public_id' => (string) Str::uuid(), 'is_local' => false]);
        }

        return response()->json(['id' => $site->id]);
    }

    private function origin(string $url): string
    {
        $p = parse_url($url);

        return isset($p['scheme'], $p['host']) && ! isset($p['user']) && ! isset($p['pass']) ? $p['scheme'].'://'.$p['host'].(isset($p['port']) ? ':'.$p['port'] : '') : '';
    }

    public function saveSource(Request $request, Site $site, ?Source $source = null)
    {
        $data = $request->validate(['kind' => ['required', Rule::in(['faq', 'page', 'sitemap'])], 'title' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'url:http,https', 'max:2048'], 'content' => ['nullable', 'string', 'max:50000'], 'enabled' => ['required', 'boolean']]);
        if ($source?->exists) {
            abort_unless($source->site_id === $site->id && $source->kind !== 'local', 404);
        }
        abort_if($site->is_local && $data['kind'] !== 'faq', 422, 'Local website pages are synchronized directly; add FAQs here.');
        if ($data['kind'] !== 'faq') {
            abort_unless($data['url'] && $this->origin($data['url']) === $this->origin($site->url) && str_starts_with($data['url'], 'https://'), 422, 'Import URLs must use HTTPS on the website origin.');
            abort_if(Source::where('site_id', $site->id)->where('kind', $data['kind'])->when($source?->id, fn ($q) => $q->where('id', '!=', $source->id))->count() >= ($data['kind'] === 'page' ? config('ai_pro.max_pages') : 5), 422, 'Import limit reached.');
        } else {
            abort_unless(trim($data['content'] ?? '') !== '', 422, 'Enter FAQ content.');
            abort_if(Source::where('site_id', $site->id)->where('kind', 'faq')->when($source?->id, fn ($q) => $q->where('id', '!=', $source->id))->count() >= 100, 422, 'FAQ limit reached.');
        }
        $url = $data['url'] ?: $site->url;
        abort_unless($this->origin($url) === $this->origin($site->url), 422, 'Source URL must belong to this website.');
        $key = $source?->source_key ?? ($data['kind'] === 'faq' ? (string) Str::uuid() : hash('sha256', $url));
        $record = app(Knowledge::class)->save($site, $key, $data['kind'], ['title' => $data['title'], 'url' => $url,
            'content' => $data['kind'] === 'faq' ? LocalContent::text($data['content']) : '']);
        $record->update(['enabled' => $data['enabled']]);
        SyncSite::enqueue($site->id);

        return response()->json(['id' => $record->id]);
    }

    public function toggleSource(Request $request, Site $site, Source $source)
    {
        abort_unless($source->site_id === $site->id, 404);
        $source->update($request->validate(['enabled' => ['required', 'boolean']]));
        if ($source->enabled) {
            SyncSite::enqueue($site->id);
        }

        return response()->json(['success' => 'Source updated.']);
    }

    public function deleteSource(Site $site, Source $source)
    {
        abort_unless($source->site_id === $site->id && $source->kind !== 'local', 404);
        $source->delete();

        return response()->json(['success' => 'Source deleted.']);
    }

    public function sync(Site $site)
    {
        SyncSite::enqueue($site->id);

        return response()->json(['success' => 'Synchronization queued.']);
    }

    public function messages(Site $site, Conversation $conversation)
    {
        abort_unless($conversation->site_id === $site->id, 404);

        return response()->json(Message::where('site_id', $site->id)->where('conversation_id', $conversation->id)->latest('id')->paginate(30));
    }
}
