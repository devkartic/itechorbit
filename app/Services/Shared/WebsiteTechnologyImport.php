<?php

namespace App\Services\Shared;

use App\Models\Admin\ServiceInfo;
use App\Models\Admin\WebsiteTechnology;
use Illuminate\Support\Str;

class WebsiteTechnologyImport
{
    public const HOMEPAGE = ['Laravel', 'PHP', 'React', 'TypeScript', 'REST APIs', 'SQL'];

    // Legacy creation is limited to the original migration, before the catalog schema exists.
    // After consolidation, imports resolve admin-managed names/aliases and never create entries.
    public static function service(ServiceInfo $service): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('website_technology_aliases')) {
            self::legacyService($service);
            return;
        }
        $ids = [];
        $unknown = [];
        foreach ($service->getAttribute('technologies') ?? [] as $label) {
            if (trim($label) === '') {
                continue;
            }
            $technology = app(TechnologyCatalog::class)->resolve($label, 'name');
            if ($technology) {
                $ids[] = $technology->id;
            } else {
                $unknown[] = $label;
            }
        }
        if ($unknown) {
            throw new \RuntimeException('Unknown technologies; create them in Website Control first: '.implode(', ', $unknown));
        }
        $service->websiteTechnologies()->syncWithoutDetaching(array_unique($ids));
    }

    private static function legacyService(ServiceInfo $service): void
    {
        $ids = [];
        foreach ($service->getAttribute('technologies') ?? [] as $label) {
            $name = trim($label);
            if ($name === '') {
                continue;
            }
            $key = mb_strtolower($name);
            $name = match ($key) {
                'rest', 'rest apis' => 'REST APIs', 'mysql', 'sql' => 'SQL',
                'laravel' => 'Laravel', 'php' => 'PHP', 'react' => 'React', 'typescript' => 'TypeScript',
                default => $name,
            };
            $technology = WebsiteTechnology::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
            if (! $technology) {
                $slug = Str::slug($name) ?: 'technology';
                $candidate = $slug;
                $suffix = 2;
                while (WebsiteTechnology::where('slug', $candidate)->exists()) {
                    $candidate = $slug.'-'.$suffix++;
                }
                $position = array_search($name, self::HOMEPAGE, true);
                $technology = WebsiteTechnology::create(['name' => $name, 'slug' => $candidate, 'status' => true,
                    'show_on_homepage' => $position !== false, 'display_order' => $position === false ? 100 : $position]);
            }
            $ids[] = $technology->id;
        }
        $service->websiteTechnologies()->syncWithoutDetaching($ids);
    }
}
