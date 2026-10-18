<?php

namespace App\AiPro;

use App\AiPro\Contracts\AiProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiProvider implements AiProvider
{
    private function request(string $model, string $method, array $body, bool $embedding = false): array
    {
        $key = config($embedding ? 'ai_pro.embedding_api_key' : 'ai_pro.api_key');
        if (! $key) {
            throw new ProviderFailure('credentials');
        }
        if (! preg_match('/^[a-zA-Z0-9._-]+$/D', $model)) {
            throw new ProviderFailure('model');
        }
        // Header authentication avoids recording credentials in URL logs.
        $response = Http::withHeaders(['x-goog-api-key' => $key])->acceptJson()->connectTimeout(5)->timeout(35)
            ->post('https://generativelanguage.googleapis.com/v1beta/models/'.$model.':'.$method, $body);
        ProviderFailure::check($response);
        $data = $response->json();
        if (! is_array($data)) {
            throw new RuntimeException('Invalid Gemini response.');
        }

        return $data;
    }

    public function embed(array $texts, string $purpose = 'document'): array
    {
        $model = config('ai_pro.embedding_model');
        $vectors = [];
        // Individual standard requests keep one vector per chunk; Embedding 2
        // aggregates multiple parts and the asynchronous Batch API requires billing.
        foreach ($texts as $text) {
            $body = ['content' => ['parts' => [['text' => $text]]], 'outputDimensionality' => 768];
            if ($model === 'gemini-embedding-001') {
                $body['taskType'] = $purpose === 'query' ? 'RETRIEVAL_QUERY' : 'RETRIEVAL_DOCUMENT';
            } else {
                [$title, $content] = array_pad(explode("\n", $text, 2), 2, $text);
                $body['content']['parts'][0]['text'] = $purpose === 'query'
                    ? 'task: question answering | query: '.$text : 'title: '.$title.' | text: '.$content;
            }
            $data = $this->request($model, 'embedContent', $body, true);
            $vectors[] = AnswerFormat::vector($data['embedding']['values'] ?? null);
        }

        return $vectors;
    }

    public function answer(string $question, array $context, array $history): array
    {
        $response = $this->request(config('ai_pro.model'), 'generateContent', [
            'systemInstruction' => ['parts' => [['text' => AnswerFormat::instructions()]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => json_encode(['sources' => $context, 'history' => $history, 'question' => $question], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]]]],
            'generationConfig' => ['responseMimeType' => 'application/json', 'responseJsonSchema' => AnswerFormat::schema(), 'maxOutputTokens' => 4096],
        ]);
        $candidate = $response['candidates'][0] ?? [];
        if (($candidate['finishReason'] ?? '') !== 'STOP') {
            throw new RuntimeException('Gemini did not return a complete answer.');
        }
        $text = collect($candidate['content']['parts'] ?? [])->reject(fn ($part) => $part['thought'] ?? false)->pluck('text')->implode('');

        return AnswerFormat::parse($text, $response['usageMetadata'] ?? []);
    }
}
