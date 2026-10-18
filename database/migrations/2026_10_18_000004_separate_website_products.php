<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle');
            $table->text('descriptions');
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
            $table->string('slug')->unique();
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
        foreach (DB::table('website_services')->where('service_type', 'product')->orderBy('id')->get() as $record) {
            $data = (array) $record;
            unset($data['service_type']);
            DB::table('website_products')->insert($data);
        }
        DB::table('website_services')->where('service_type', 'product')->delete();
        Schema::table('website_services', function (Blueprint $table) {
            $table->dropIndex(['service_type']);
            $table->dropColumn(['service_type', 'price', 'currency', 'delivery_time']);
        });
    }

    public function down(): void
    {
        // Independent sequences can overlap after new records are created. Never overwrite them.
        if (DB::table('website_products')->whereIn('id', DB::table('website_services')->select('id'))->exists()
            || DB::table('website_products')->whereIn('slug', DB::table('website_services')->select('slug'))->exists()) {
            throw new RuntimeException('Resolve overlapping website product/service IDs or slugs before rollback. No records were changed.');
        }
        Schema::table('website_services', function (Blueprint $table) {
            $table->string('service_type', 20)->default('custom')->index();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('delivery_time')->nullable();
        });
        foreach (DB::table('website_products')->orderBy('id')->get() as $record) {
            DB::table('website_services')->insert([...(array) $record, 'service_type' => 'product']);
        }
        Schema::drop('website_products');
    }
};
