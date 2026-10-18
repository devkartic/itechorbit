<?php

namespace App\AiPro\Jobs;

use App\AiPro\Knowledge;
use App\AiPro\LocalContent;
use App\AiPro\Models\Site;
use App\AiPro\Models\Source;
use App\AiPro\Models\SyncRun;
use App\AiPro\ProviderFailure;
use App\AiPro\SafePageFetcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class SyncSite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 2;

    public int $backoff = 30;

    public function __construct(public int $siteId, public ?int $runId = null, public int $providerRetries = 0)
    {
        $this->onConnection('ai_pro')->onQueue('ai-pro');
    }

    public static function enqueue(int $siteId): SyncRun
    {
        return DB::transaction(function () use ($siteId) {
            $site = Site::whereKey($siteId)->lockForUpdate()->firstOrFail();
            $active = SyncRun::where('site_id', $siteId)->whereIn('status', ['queued', 'running'])->where('updated_at', '>', now()->subMinutes(15))->first();
            if ($active) {
                if ($active->status === 'running') {
                    $site->update(['resync_requested' => true]);
                }

                return $active;
            }
            $run = SyncRun::create(['site_id' => $siteId]);
            self::dispatch($siteId, $run->id)->afterCommit();

            return $run;
        });
    }

    public function handle(Knowledge $knowledge, LocalContent $local, SafePageFetcher $fetcher): void
    {
        $site = Site::find($this->siteId);
        if (! $site) {
            return;
        }
        $run = $this->runId ? SyncRun::find($this->runId) : null;
        $run ??= SyncRun::create(['site_id' => $site->id]);
        $site->update(['resync_requested' => false]);
        $run->update(['status' => 'running', 'errors' => [], 'processed' => 0]);
        $errors = [];
        try {
            if ($site->is_local) {
                $snapshot = $local->snapshot($site);
                Source::where('site_id', $site->id)->where('kind', 'local')->whereNotIn('source_key', array_keys($snapshot))->delete();
                foreach ($snapshot as $key => $item) {
                    $knowledge->save($site, $key, 'local', $item);
                }
            }
            $sitemaps = Source::where('site_id', $site->id)->where('kind', 'sitemap')->where('enabled', true)->get();
            foreach ($sitemaps as $map) {
                try {
                    $urls = $this->sitemapUrls($site, $map->url, $fetcher);
                    foreach ($urls as $url) {
                        if (! Source::where('site_id', $site->id)->where('source_key', hash('sha256', $url))->exists()) {
                            $knowledge->save($site, hash('sha256', $url), 'page', ['title' => $url, 'url' => $url, 'content' => '']);
                        }
                    }
                    $map->update(['status' => 'ready']);
                } catch (Throwable) {
                    $map->update(['status' => 'failed']);
                    $errors[] = 'Sitemap #'.$map->id.' could not be imported. Check its URL, robots rules and network access.';
                }
            }
            foreach (Source::where('site_id', $site->id)->where('enabled', true)->where('kind', '!=', 'sitemap')->orderBy('id')->get() as $source) {
                try {
                    if ($source->kind === 'page') {
                        $html = $fetcher->fetch($site, $source->url);
                        if (preg_match('/<meta[^>]+name=["\']robots["\'][^>]+content=["\'][^"\']*noindex/i', $html)) {
                            throw new \RuntimeException('Page is not indexable.');
                        }
                        preg_match('#<title[^>]*>(.*?)</title>#is', $html, $title);
                        $source = $knowledge->save($site, $source->source_key, 'page', ['title' => mb_substr(LocalContent::text($title[1] ?? $source->url), 0, 255), 'url' => $source->url, 'content' => LocalContent::text($html)]);
                    }
                    $knowledge->index($source);
                    $run->increment('processed');
                } catch (ProviderFailure $e) {
                    $source->update(['status' => $e->retryAfter !== null ? 'pending' : 'failed']);
                    $this->providerFailure($run, $e);

                    return;
                } catch (Throwable) {
                    $source->update(['status' => 'failed']);
                    $errors[] = 'Source #'.$source->id.' could not be indexed. Check provider configuration, content limits and import access.';
                }
            }
            $run->update(['status' => $errors ? 'partial' : 'completed', 'errors' => $errors, 'finished_at' => now()]);
            if ($site->fresh()?->resync_requested) {
                self::enqueue($site->id);
            }
        } catch (Throwable $e) {
            $run->update(['status' => 'failed', 'errors' => ['Synchronization failed. Check queue and provider configuration.'], 'finished_at' => now()]);
            throw $e;
        }
    }

    private function providerFailure(SyncRun $run, ProviderFailure $error): void
    {
        if ($error->retryAfter !== null && $this->providerRetries < 3) {
            $delay = max($error->retryAfter, [60, 120, 300][$this->providerRetries]);
            $run->update(['status' => 'queued', 'errors' => [$error->getMessage().' Retry '.($this->providerRetries + 1).'/3 scheduled.'], 'finished_at' => null]);
            self::dispatch($this->siteId, $run->id, $this->providerRetries + 1)->delay(now()->addSeconds($delay))->afterCommit();

            return;
        }
        $run->update(['status' => 'failed', 'errors' => [$error->getMessage().' Retry synchronization after correcting the problem.'], 'finished_at' => now()]);
    }

    private function sitemapUrls(Site $site, string $url, SafePageFetcher $fetcher): array
    {
        $pending = [$url];
        $seen = [];
        $pages = [];
        while ($pending) {
            $url = array_shift($pending);
            if (isset($seen[$url])) {
                continue;
            }
            if (count($seen) >= 10) {
                throw new \RuntimeException('Sitemap limit exceeded.');
            }
            $seen[$url] = true;
            $xml = $fetcher->fetch($site, $url);
            if (stripos($xml, '<!DOCTYPE') !== false || stripos($xml, '<!ENTITY') !== false) {
                throw new \RuntimeException('Unsafe XML.');
            }
            $doc = new \DOMDocument;
            if (! @$doc->loadXML($xml, LIBXML_NONET)) {
                throw new \RuntimeException('Invalid sitemap.');
            }
            $index = $doc->documentElement?->localName === 'sitemapindex';
            foreach ($doc->getElementsByTagName('loc') as $node) {
                $location = trim($node->textContent);
                $fetcher->validate($site, $location);
                if ($index) {
                    $pending[] = $location;
                } else {
                    $pages[$location] = $location;
                }
                if (count($pages) > config('ai_pro.max_pages') || count($pending) > 10) {
                    throw new \RuntimeException('Import limit exceeded.');
                }
            }
        }
        $existing = Source::where('site_id', $site->id)->where('kind', 'page')->whereNotIn('url', $pages)->count();
        if ($existing + count($pages) > config('ai_pro.max_pages')) {
            throw new \RuntimeException('Website page limit exceeded.');
        }

        return array_values($pages);
    }

    public function failed(?Throwable $exception): void
    {
        if ($this->runId) {
            SyncRun::whereKey($this->runId)->update(['status' => 'failed', 'errors' => ['Worker failed or timed out. Retry synchronization.'], 'finished_at' => now()]);
        }
    }
}
