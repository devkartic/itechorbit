<?php

namespace App\AiPro;

use Illuminate\Http\Client\Response;
use RuntimeException;

class ProviderFailure extends RuntimeException
{
    public function __construct(public string $reason, public ?int $retryAfter = null)
    {
        parent::__construct(match ($reason) {
            'credentials' => 'Provider credentials were rejected. Check the selected provider key and permissions.',
            'quota' => 'Provider quota reached. Indexing will retry later; check your free-tier limits.',
            'model' => 'The configured provider model or endpoint is unavailable.',
            default => 'The AI provider is temporarily unavailable.',
        });
    }

    public static function check(Response $response): void
    {
        if ($response->successful()) {
            return;
        }
        $reason = match ($response->status()) {
            401, 403 => 'credentials', 429 => 'quota', 400, 404 => 'model', default => 'unavailable',
        };
        $keyError = in_array($response->json('error.code'), ['invalid_api_key', 'API_KEY_INVALID'], true)
            || in_array($response->json('error.status'), ['UNAUTHENTICATED', 'PERMISSION_DENIED'], true);
        foreach ($response->json('error.details') ?? [] as $detail) {
            $keyError = $keyError || str_starts_with($detail['reason'] ?? '', 'API_KEY_');
        }
        if ($keyError) {
            $reason = 'credentials';
        }
        $delay = $response->header('Retry-After');
        $delay = is_numeric($delay) ? (int) $delay : ($delay ? max(0, (int) strtotime($delay) - time()) : 60);
        // Google's RetryInfo carries a duration when Retry-After is not supplied.
        foreach ($response->json('error.details') ?? [] as $detail) {
            if (isset($detail['retryDelay']) && preg_match('/^([0-9.]+)s$/', $detail['retryDelay'], $match)) {
                $delay = max($delay, (int) ceil((float) $match[1]));
            }
        }
        throw new self($reason, in_array($reason, ['quota', 'unavailable'], true) ? max(30, min(3600, $delay)) : null);
    }
}
