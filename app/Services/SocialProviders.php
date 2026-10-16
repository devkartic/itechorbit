<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;

class SocialProviders
{
    public const PROVIDERS = ['google' => 'Google', 'linkedin' => 'LinkedIn', 'github' => 'GitHub'];

    public function driver(string $provider): string
    {
        return $provider === 'linkedin' ? 'linkedin-openid' : $provider;
    }

    public function enabled(string $provider): bool
    {
        $config = config('services.'.$this->driver($provider), []);

        return isset(self::PROVIDERS[$provider]) && filled($config['client_id'] ?? null)
            && filled($config['client_secret'] ?? null) && filled($config['redirect'] ?? null)
            && Schema::hasTable('social_accounts');
    }

    public function available(): array
    {
        return collect(self::PROVIDERS)->filter(fn ($label, $provider) => $this->enabled($provider))
            ->map(fn ($label, $provider) => ['id' => $provider, 'name' => $label, 'url' => '/login/'.$provider])->values()->all();
    }
}
