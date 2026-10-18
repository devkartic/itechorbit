<?php

namespace App\AiPro;

use App\AiPro\Contracts\AiProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CompatibleProvider implements AiProvider
{
    private function request(string $path, array $body, bool $embedding = false): array
    {
        $url = rtrim(config('ai_pro.compatible_base_url') ?? '', '/');
        $parts = parse_url($url);
        if (! filter_var($url, FILTER_VALIDATE_URL) || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            throw new RuntimeException('Configure a valid OpenAI-compatible base URL.');
        }
        $key = config($embedding ? 'ai_pro.embedding_api_key' : 'ai_pro.api_key');
        $request = Http::acceptJson()->connectTimeout(5)->timeout(35);
        if ($key) {
            $request = $request->withToken($key);
        }
        // The base URL is trusted server configuration, never visitor input.
        $response = $request->withOptions(['allow_redirects' => false])->post($url.'/'.$path, $body);
        ProviderFailure::check($response);
        $data = $response->json();
        if (! is_array($data)) {
            throw new RuntimeException('Invalid compatible provider response.');
        }

        return $data;
    }

    public function embed(array $texts, string $purpose = 'document'): array
    {
        $data = $this->request('embeddings', ['model' => config('ai_pro.embedding_model'), 'input' => array_values($texts)], true);
        $vectors = collect($data['data'] ?? [])->sortBy('index')->pluck('embedding')->all();
        if (count($vectors) !== count($texts)) {
            throw new RuntimeException('Incomplete embeddings.');
        }

        return array_map(AnswerFormat::vector(...), $vectors);
    }

    public function answer(string $question, array $context, array $history): array
    {
        $data = $this->request('chat/completions', ['model' => config('ai_pro.model'), 'max_tokens' => 1200,
            'messages' => [['role' => 'system', 'content' => AnswerFormat::instructions()], ['role' => 'user', 'content' => json_encode(['sources' => $context, 'history' => $history, 'question' => $question], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]],
            'response_format' => ['type' => 'json_object'],
        ]);
        $choice = $data['choices'][0] ?? [];
        if (($choice['finish_reason'] ?? '') !== 'stop') {
            throw new RuntimeException('Provider did not return a complete answer.');
        }

        return AnswerFormat::parse($choice['message']['content'] ?? '', $data['usage'] ?? []);
    }
}
