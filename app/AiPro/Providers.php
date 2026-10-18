<?php

namespace App\AiPro;

use App\AiPro\Contracts\AiProvider;
use RuntimeException;

class Providers implements AiProvider
{
    public static function resolve(string $provider): AiProvider
    {
        return match ($provider) {
            'openai' => app(OpenAiProvider::class),
            'gemini' => app(GeminiProvider::class),
            'compatible' => app(CompatibleProvider::class),
            default => throw new RuntimeException('Unsupported AI provider. Select openai, gemini or compatible.'),
        };
    }

    public static function embeddingIdentity(): string
    {
        $model = (string) config('ai_pro.embedding_model');

        // Compatible services can reuse model names for unrelated embedding spaces.
        return config('ai_pro.embedding_provider') === 'compatible'
            ? 'compatible:'.hash('sha256', rtrim(config('ai_pro.compatible_base_url') ?? '', '/').'|'.$model)
            : $model;
    }

    public static function ready(): bool
    {
        foreach ([false, true] as $embedding) {
            $provider = config($embedding ? 'ai_pro.embedding_provider' : 'ai_pro.provider');
            if (! in_array($provider, ['openai', 'gemini', 'compatible'], true) || ! config($embedding ? 'ai_pro.embedding_model' : 'ai_pro.model')) {
                return false;
            }
            if ($provider === 'compatible') {
                if (! filter_var(config('ai_pro.compatible_base_url'), FILTER_VALIDATE_URL)) {
                    return false;
                }
            } elseif (! config($embedding ? 'ai_pro.embedding_api_key' : 'ai_pro.api_key')) {
                return false;
            }
        }

        return true;
    }

    public function embed(array $texts, string $purpose = 'document'): array
    {
        return self::resolve(config('ai_pro.embedding_provider', 'openai'))->embed($texts, $purpose);
    }

    public function answer(string $question, array $context, array $history): array
    {
        return self::resolve(config('ai_pro.provider', 'openai'))->answer($question, $context, $history);
    }
}
