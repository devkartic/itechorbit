<?php

use Database\Seeders\WorkspaceNavigationSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new WorkspaceNavigationSeeder)->run();
    }

    public function down(): void
    {
        // Retain navigation records: administrators may have edited them or assigned permissions.
    }
};
