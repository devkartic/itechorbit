<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_pages', function (Blueprint $table) {
            $table->id();
            $table->string('page_key', 30)->unique();
            $table->string('heading');
            $table->text('introduction');
            $table->string('cta_label')->nullable();
            $table->string('cta_url', 2048)->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('share_image', 2048)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        foreach (require database_path('data/public_pages.php') as $key => $values) {
            DB::table('website_pages')->insert([...$values, 'page_key' => $key,
                'cta_label' => $values['cta_label'] ?? 'Discuss your project', 'cta_url' => '/contact/info',
                'created_at' => now(), 'updated_at' => now()]);
        }
        $module = DB::table('links')->where('url', 'service-info')->value('module_id');
        if (! $module) {
            $menu = DB::table('menus')->where('name', 'Website')->value('id')
                ?? DB::table('menus')->insertGetId(['name' => 'Website', 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
            $module = DB::table('modules')->where('menu_id', $menu)->where('name', 'Site Control')->value('id')
                ?? DB::table('modules')->insertGetId(['menu_id' => $menu, 'name' => 'Site Control', 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('links')->insertOrIgnore(['url' => 'public-pages', 'name' => 'Public pages', 'module_id' => $module, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        throw new RuntimeException('Restore the pre-upgrade backup to preserve edited public page content.');
    }
};
