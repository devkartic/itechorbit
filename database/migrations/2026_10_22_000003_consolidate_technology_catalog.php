<?php

use App\Models\Admin\WebsiteTechnology;
use App\Services\Shared\TechnologyCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $backup = storage_path('app/private/technology-backups/'.Str::uuid().'.json');
        File::ensureDirectoryExists(dirname($backup), 0700);
        File::put($backup, json_encode([
            'technologies' => DB::table('website_technologies')->orderBy('id')->get()->all(),
            'assignments' => DB::table('website_service_technology')->get()->all(),
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        chmod($backup, 0600);

        Schema::table('website_technologies', fn (Blueprint $table) => $table->boolean('show_in_filters')->default(true));
        Schema::create('website_technology_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_technology_id')->constrained('website_technologies')->cascadeOnDelete();
            $table->string('type', 10);
            $table->string('value', 180);
            $table->unique(['type', 'value'], 'technology_alias_unique');
        });

        DB::transaction(function () {
            $catalog = app(TechnologyCatalog::class);
            $order = 0;
            foreach (require database_path('data/technology_catalog.php') as $slug => $definition) {
                $names = array_map('mb_strtolower', $definition['members']);
                $members = WebsiteTechnology::whereIn(DB::raw('LOWER(name)'), $names)->orderBy('id')->get();
                $target = $members->first(fn ($item) => mb_strtolower($item->name) === mb_strtolower($definition['name']));
                $target ??= $members->first();
                if (! $target) {
                    $target = WebsiteTechnology::create(['name' => $definition['name'], 'slug' => $slug, 'status' => true,
                        'display_order' => $order, 'show_on_homepage' => $order < 6, 'show_in_filters' => true]);
                }
                // Preserve existing placement, ordering and publication choices on retained records.
                $catalog->update($target, ['name' => $definition['name'], 'slug' => $slug]);
                foreach ($members as $member) {
                    if ($member->id !== $target->id) {
                        $catalog->merge($member, $target);
                    }
                }
                foreach ($definition['members'] as $name) {
                    $catalog->alias($target, 'name', $name);
                    $legacySlug = Str::slug($name);
                    if ($legacySlug !== $slug) {
                        $catalog->alias($target, 'slug', $legacySlug);
                    }
                }
                foreach ($definition['groups'] ?? [] as $group) {
                    $catalog->alias($target, 'group', $group);
                }
                $order++;
            }
            // Previously combined React included Inertia; keep the old group URL working.
            $catalog->alias(WebsiteTechnology::where('slug', 'react')->firstOrFail(), 'group', 'react');
            $catalog->alias(WebsiteTechnology::where('slug', 'laravel')->firstOrFail(), 'group', 'laravel');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Restore the technology-backups JSON snapshot to reverse this data consolidation; automatic rollback would lose assignment history.');
    }
};
