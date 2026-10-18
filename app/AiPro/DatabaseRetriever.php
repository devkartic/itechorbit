<?php

namespace App\AiPro;

use App\AiPro\Contracts\AiProvider;
use App\AiPro\Contracts\Retriever;
use App\AiPro\Models\Chunk;
use App\AiPro\Models\Site;
use App\AiPro\Models\Source;
use Illuminate\Support\Collection;

class DatabaseRetriever implements Retriever
{
    public function search(Site $site, string $question): array
    {
        $sources = $this->eligibleSources($site);
        if ($sources->isEmpty()) {
            return [];
        }
        $vector = app(AiProvider::class)->embed([$question], 'query')[0];
        $matches = [];
        foreach (Chunk::where('site_id', $site->id)->whereIn('source_id', $sources->keys())->where('embedding_model', Providers::embeddingIdentity())->cursor() as $chunk) {
            $score = self::cosine($vector, $chunk->embedding);
            if ($score >= 0.25) {
                $source = $sources[$chunk->source_id];
                $matches[] = ['id' => $source->id, 'title' => $source->title, 'url' => $source->url, 'text' => $chunk->content, 'checksum' => $source->checksum, 'score' => $score];
            }
        }
        usort($matches, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($matches, 0, 6);
    }

    public function eligibleSources(Site $site): Collection
    {
        $snapshot = $site->is_local ? app(LocalContent::class)->snapshot($site) : [];
        $sources = Source::where('site_id', $site->id)->where('enabled', true)->where('status', 'ready')->get()->filter(function ($source) use ($snapshot) {
            return $source->kind !== 'local' || (isset($snapshot[$source->source_key]) && LocalContent::checksum($snapshot[$source->source_key]) === $source->checksum);
        })->keyBy('id');

        return $sources;
    }

    public static function cosine(array $a, array $b): float
    {
        if (! count($a) || count($a) !== count($b)) {
            return 0;
        }
        $dot = $na = $nb = 0;
        foreach ($a as $i => $value) {
            $dot += $value * $b[$i];
            $na += $value ** 2;
            $nb += $b[$i] ** 2;
        }

        return $na && $nb ? $dot / sqrt($na * $nb) : 0;
    }
}
