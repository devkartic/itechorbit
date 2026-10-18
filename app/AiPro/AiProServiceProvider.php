<?php

namespace App\AiPro;

use App\AiPro\Contracts\AiProvider;
use App\AiPro\Contracts\Retriever;
use App\AiPro\Jobs\SyncSite;
use App\AiPro\Models\Site;
use App\Models\Admin\AboutInfo;
use App\Models\Admin\ContactInfo;
use App\Models\Admin\Post;
use App\Models\Admin\PrivacyPolicy;
use App\Models\Admin\Project;
use App\Models\Admin\ServiceInfo;
use App\Models\Admin\WebsiteProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AiProServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AiProvider::class, Providers::class);
        $this->app->bind(Retriever::class, DatabaseRetriever::class);
    }

    public function boot(): void
    {
        foreach ([Post::class, ServiceInfo::class, WebsiteProduct::class, Project::class, AboutInfo::class, ContactInfo::class, PrivacyPolicy::class, User::class] as $model) {
            foreach (['saved', 'deleted'] as $event) {
                $model::$event(function () {
                    if (! config('ai_pro.enabled') || ! Schema::hasTable('ai_pro_sites')) {
                        return;
                    }
                    DB::afterCommit(function () {
                        foreach (Site::where('is_local', true)->get() as $site) {
                            SyncSite::enqueue($site->id);
                        }
                    });
                });
            }
        }
    }
}
