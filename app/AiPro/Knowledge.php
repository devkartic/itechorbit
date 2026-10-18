<?php

namespace App\AiPro;

use App\AiPro\Contracts\AiProvider;
use App\AiPro\Models\Chunk;
use App\AiPro\Models\Site;
use App\AiPro\Models\Source;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Knowledge
{
    public function save(Site $site, string $key, string $kind, array $item): Source
    {
        $checksum = LocalContent::checksum($item);
        $source = Source::firstOrNew(['site_id' => $site->id, 'source_key' => $key]);
        if (! $source->exists || $source->checksum !== $checksum) {
            $source->fill([...$item, 'kind' => $kind, 'checksum' => $checksum, 'status' => 'pending']);
            $source->save();
        }

        return $source;
    }

    public function index(Source $source): void
    {
        if (! $source->enabled) {
            return;
        }
        if ($source->status === 'ready' && ! Chunk::where('source_id', $source->id)->where('embedding_model', '!=', Providers::embeddingIdentity())->exists()) {
            return;
        }
        $checksum = $source->checksum;
        $texts = [];
        // Character windows bound multilingual inputs without requiring a tokenizer.
        for ($start = 0, $length = mb_strlen($source->content); $start < $length; $start += 1200) {
            $texts[] = $source->title."\n".mb_substr($source->content, $start, 1500);
            if (count($texts) > config('ai_pro.max_chunks')) {
                throw new RuntimeException('Website knowledge limit exceeded.');
            }
        }
        if (Chunk::where('site_id', $source->site_id)->where('source_id', '!=', $source->id)->count() + count($texts) > config('ai_pro.max_chunks')) {
            throw new RuntimeException('Website knowledge limit exceeded.');
        }
        $vectors = [];
        foreach (array_chunk($texts, 32) as $batch) {
            $vectors = [...$vectors, ...app(AiProvider::class)->embed($batch)];
        }
        DB::transaction(function () use ($source, $texts, $vectors, $checksum) {
            Site::whereKey($source->site_id)->lockForUpdate()->firstOrFail();
            $current = Source::whereKey($source->id)->lockForUpdate()->first();
            if (! $current || ! $current->enabled || $current->checksum !== $checksum) {
                return;
            }
            $existing = Chunk::where('site_id', $source->site_id)->where('source_id', '!=', $source->id)->count();
            if ($existing + count($texts) > config('ai_pro.max_chunks')) {
                throw new RuntimeException('Website knowledge limit exceeded.');
            }
            Chunk::where('source_id', $source->id)->delete();
            foreach ($texts as $index => $text) {
                Chunk::create(['site_id' => $source->site_id, 'source_id' => $source->id, 'content' => $text, 'embedding' => $vectors[$index], 'embedding_model' => Providers::embeddingIdentity()]);
            }
            $current->update(['status' => 'ready']);
        });
    }
}
