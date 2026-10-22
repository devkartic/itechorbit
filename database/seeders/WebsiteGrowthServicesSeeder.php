<?php

namespace Database\Seeders;

use App\Models\Admin\ServiceInfo;
use App\Services\Shared\WebsiteTechnologyImport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WebsiteGrowthServicesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $order = 0;
            foreach (require database_path('data/website_growth_services.php') as $slug => $service) {
                // Keep existing publication choices, ordering and edits made through the workspace.
                $record = ServiceInfo::firstOrCreate(['slug' => $slug], [
                    ...$service, 'short_title' => $service['title'],
                    'status' => true, 'highlight' => false, 'display_order' => $order += 10,
                ]);
                if ($record->wasRecentlyCreated) {
                    WebsiteTechnologyImport::service($record);
                }
            }
        });
    }
}
