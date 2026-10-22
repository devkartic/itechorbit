<?php

use App\Models\Admin\ServiceInfo;
use App\Services\Shared\WebsiteTechnologyImport;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_technologies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 180)->unique();
            $table->boolean('status')->default(true)->index();
            $table->boolean('show_on_homepage')->default(false);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });
        Schema::create('website_service_technology', function (Blueprint $table) {
            $table->foreignId('website_service_id')->constrained('website_services')->cascadeOnDelete();
            $table->foreignId('website_technology_id')->constrained('website_technologies')->cascadeOnDelete();
            $table->unique(['website_service_id', 'website_technology_id'], 'website_service_technology_unique');
            $table->timestamps();
        });
        ServiceInfo::orderBy('id')->each(fn ($service) => WebsiteTechnologyImport::service($service));
    }

    public function down(): void
    {
        Schema::dropIfExists('website_service_technology');
        Schema::dropIfExists('website_technologies');
    }
};
