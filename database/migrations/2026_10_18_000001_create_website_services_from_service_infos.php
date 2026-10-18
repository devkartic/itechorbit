<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('service_infos', 'website_services');
        Schema::table('website_services', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
            $table->string('service_type', 20)->default('custom')->index();
            $table->string('short_title')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('display_order')->default(0)->index();
            $table->string('icon', 30)->default('code');
            $table->boolean('highlight')->default(false);
            $table->text('problem')->nullable();
            $table->text('solution')->nullable();
            $table->json('deliverables')->nullable();
            $table->json('technologies')->nullable();
            $table->json('approach')->nullable();
            $table->json('use_cases')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('delivery_time')->nullable();
        });

        // Preserve every legacy record, including its ID, publication status and HTML.
        foreach (DB::table('website_services')->orderBy('id')->get() as $record) {
            DB::table('website_services')->where('id', $record->id)->update([
                'slug' => 'legacy-'.rtrim(Str::substr(Str::slug($record->title) ?: 'service', 0, 140), '-').'-'.$record->id,
            ]);
        }

        $services = require database_path('data/website_services.php');
        $order = 0;
        foreach ($services as $slug => $service) {
            DB::table('website_services')->insert([
                'slug' => $slug, 'service_type' => 'custom', 'title' => $service['title'],
                'short_title' => $service['short'], 'subtitle' => $service['summary'],
                'descriptions' => '<h2>The challenge</h2><p>'.e($service['problem']).'</p><h2>A considered solution</h2><p>'.e($service['solution']).'</p>',
                'icon' => $service['icon'], 'highlight' => $service['highlight'] ?? false,
                'deliverables' => json_encode($service['deliverables']), 'technologies' => json_encode($service['technologies']),
                'approach' => json_encode($service['approach'] ?? [
                    'Review your goals, existing systems and constraints.',
                    'Agree on a scoped plan, architecture and acceptance criteria.',
                    'Implement in reviewable stages and validate against the requirements.',
                    'Prepare a documented handover and deployment approach.',
                ]),
                'use_cases' => isset($service['useCases']) ? json_encode($service['useCases']) : null,
                'status' => 1, 'display_order' => ++$order,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        Schema::table('website_services', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        // Retain all service records on rollback; only the added structure is removed.
        Schema::table('website_services', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropIndex(['service_type']);
            $table->dropIndex(['display_order']);
            $table->dropColumn(['slug', 'service_type', 'short_title', 'image_path', 'display_order', 'icon', 'highlight',
                'problem', 'solution', 'deliverables', 'technologies', 'approach', 'use_cases', 'price', 'currency', 'delivery_time']);
        });
        Schema::rename('website_services', 'service_infos');
    }
};
