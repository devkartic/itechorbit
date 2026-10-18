<?php

$provider = env('AI_PRO_PROVIDER', 'openai');
$embeddingProvider = env('AI_PRO_EMBEDDING_PROVIDER', $provider);
$key = fn ($name) => match ($name) {
    'gemini' => env('AI_PRO_GEMINI_API_KEY'),
    'compatible' => env('AI_PRO_COMPATIBLE_API_KEY'),
    default => env('AI_PRO_OPENAI_API_KEY'),
};
$model = fn ($name, $embedding) => match ($name) {
    'gemini' => $embedding ? env('AI_PRO_GEMINI_EMBEDDING_MODEL', 'gemini-embedding-2') : env('AI_PRO_GEMINI_MODEL', 'gemini-3.8-flash'),
    'compatible' => $embedding ? env('AI_PRO_COMPATIBLE_EMBEDDING_MODEL') : env('AI_PRO_COMPATIBLE_MODEL'),
    default => $embedding ? env('AI_PRO_EMBEDDING_MODEL', 'text-embedding-3-small') : env('AI_PRO_MODEL', 'gpt-4.1-mini'),
};

return [
    'enabled' => env('AI_PRO_ENABLED', false),
    'local_site' => env('AI_PRO_LOCAL_SITE', 'itechorbit'),
    'provider' => $provider,
    'embedding_provider' => $embeddingProvider,
    'api_key' => $key($provider),
    'embedding_api_key' => $key($embeddingProvider),
    'model' => $model($provider, false),
    'embedding_model' => $model($embeddingProvider, true),
    'compatible_base_url' => env('AI_PRO_COMPATIBLE_BASE_URL'),
    'max_chunks' => 2000,
    'max_pages' => 100,
    'retention_days' => 30,
];
