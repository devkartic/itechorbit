<?php

namespace App\Console\Commands;

use App\AiPro\Models\Site;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class AiProSetup extends Command
{
    protected $signature = 'ai-pro:setup';

    protected $description = 'Register this website with iTechOrbit AI (disabled until configured and indexed)';

    public function handle(): int
    {
        if (! Schema::hasTable('ai_pro_sites')) {
            $this->error('Apply migrations first.');

            return self::FAILURE;
        }
        $url = rtrim(config('app.url'), '/');
        $site = Site::firstOrCreate(['public_id' => config('ai_pro.local_site')], ['name' => config('app.name'), 'url' => $url,
            'origins' => [$url], 'is_local' => true, 'enabled' => false, 'contact_url' => $url.'/contact/info']);
        if (! $site->is_local) {
            $this->error('The configured local identifier belongs to another website.');

            return self::FAILURE;
        }
        $this->info('Local website registered. Configure iTechOrbit AI in /workspace/ai-pro, index its content, then enable the widget.');

        return self::SUCCESS;
    }
}
