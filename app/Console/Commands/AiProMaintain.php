<?php

namespace App\Console\Commands;

use App\AiPro\Jobs\SyncSite;
use App\AiPro\Models\Conversation;
use App\AiPro\Models\Site;
use App\AiPro\Models\SyncRun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class AiProMaintain extends Command
{
    protected $signature = 'ai-pro:maintain';

    protected $description = 'Purge expired chats and queue daily knowledge reconciliation';

    public function handle(): int
    {
        if (! Schema::hasTable('ai_pro_sites')) {
            return self::SUCCESS;
        }
        Conversation::where('expires_at', '<=', now())->delete();
        SyncRun::where('created_at', '<', now()->subDays(30))->delete();
        SyncRun::whereIn('status', ['queued', 'running'])->where('updated_at', '<', now()->subMinutes(15))->update(['status' => 'failed', 'errors' => ['Worker did not finish; retry synchronization.']]);
        if (config('ai_pro.enabled')) {
            Site::where('enabled', true)->each(fn ($site) => SyncSite::enqueue($site->id));
        }

        return self::SUCCESS;
    }
}
