<?php

use App\Support\Database\WebsiteContentUpgrade;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new WebsiteContentUpgrade)->run();
    }

    public function down(): void
    {
        throw new RuntimeException('Migration consolidation boundary: restore the pre-upgrade database backup and matching code to roll back.');
    }
};
