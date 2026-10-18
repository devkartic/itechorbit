<?php

namespace App\AiPro;

use App\AiPro\Contracts\AiProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiProvider implements AiProvider
{
    private function request(string $endpoint, array $body, bool $embedding = false): array
    {
        $key = config($embedding ? 'ai_pro.embedding_api_key' : 'ai_pro.api_key');
        if (! $key) {
            throw new ProviderFailure('credentials');
        }
        $response = Http::withToken($key)->acceptJson()->connectTimeout(5)->timeout(35)
            ->post('https://api.openai.com/v1/'.$endpoint, $body);
        ProviderFailure::check($response);
        $response = $response->json();
        if (! is_array($response)) {
            throw new RuntimeException('Invalid provider response.');
        }

        return $response;
    }

    public function embed(array $texts, string $purpose = 'document'): array
    {
        $data = $this->request('embeddings', ['model' => config('ai_pro.embedding_model'), 'input' => array_values($texts)], true);
        $rows = collect($data['data'] ?? [])->sortBy('index')->pluck('embedding')->all();
        if (count($rows) !== count($texts)) {
            throw new RuntimeException('Incomplete embeddings.');
        }
        foreach ($rows as $vector) {
            if (! is_array($vector) || ! count($vector) || count(array_filter($vector, fn ($n) => is_numeric($n) && is_finite((float) $n))) !== count($vector)) {
                throw new RuntimeException('Invalid embedding.');
            }
        }

        return $rows;
    }

    public function answer(string $question, array $context, array $history): array
    {
        $response = $this->request('responses', [
            'model' => config('ai_pro.model'), 'store' => false, 'max_output_tokens' => 700,
            'instructions' => AnswerFormat::instructions(),
            'input' => json_encode(['sources' => $context, 'history' => $history, 'question' => $question], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'text' => ['format' => ['type' => 'json_schema', 'name' => 'website_answer', 'strict' => true, 'schema' => [
                'type' => 'object', 'properties' => ['answer' => ['type' => 'string'], 'source_ids' => ['type' => 'array', 'items' => ['type' => 'integer']]],
                'required' => ['answer', 'source_ids'], 'additionalProperties' => false,
            ]]],
        ]);
        $text = collect($response['output'] ?? [])->where('type', 'message')->flatMap(fn ($item) => $item['content'] ?? [])->where('type', 'output_text')->pluck('text')->implode('');

        return AnswerFormat::parse($text, $response['usage'] ?? []);
    }
}
