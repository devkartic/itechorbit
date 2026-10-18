<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('contact_infos', 'website_contact');
        Schema::rename('about_infos', 'website_about');
    }

    public function down(): void
    {
        Schema::rename('website_about', 'about_infos');
        Schema::rename('website_contact', 'contact_infos');
    }
};
