<?php

use App\Support\Database\WebsiteTechnologyUpgrade;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $upgrade = new WebsiteTechnologyUpgrade;
        if (! Schema::hasTable('website_technologies')) {
            $upgrade->createCatalog();
        }
        if (! Schema::hasTable('website_technology_aliases')) {
            $upgrade->consolidateCatalog();
        }
        $upgrade->registerNavigation();
    }

    public function down(): void
    {
        throw new RuntimeException('Migration consolidation boundary: restore the pre-upgrade database backup and matching code to roll back.');
    }
};
