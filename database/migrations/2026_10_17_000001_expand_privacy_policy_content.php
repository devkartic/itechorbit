<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('privacy_policies', function (Blueprint $table) {
            $table->longText('descriptions')->change();
        });
    }

    public function down(): void
    {
        // Retain the wider column: narrowing it could truncate policies saved after this migration.
    }
};
