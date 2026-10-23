<?php

use App\Support\Database\WebsiteCatalogUpgrade;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $upgrade = new WebsiteCatalogUpgrade;
        if (Schema::hasTable('service_infos')) {
            if (Schema::hasTable('website_services')) {
                throw new RuntimeException('Both service tables exist. Resolve the conflicting tables before migrating.');
            }
            $upgrade->expandServices();
        }
        if (! Schema::hasTable('website_products')) {
            $upgrade->separateProducts();
        }
        if (Schema::hasColumn('website_services', 'service_type')) {
            throw new RuntimeException('Product separation is incomplete. Restore the pre-upgrade backup before retrying.');
        }
        $completed = DB::table('migrations')->whereIn('migration', [
            '2026_10_21_000001_refresh_website_service_marketing',
            '2026_10_24_000001_upgrade_website_catalog',
        ])->exists();
        if (! $completed) {
            $upgrade->refreshMarketing();
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Migration consolidation boundary: restore the pre-upgrade database backup and matching code to roll back.');
    }
};
