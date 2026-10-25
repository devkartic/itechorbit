<?php

namespace App\Services\Shared;

use App\Models\Admin\WebsiteTechnology;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TechnologyCatalog
{
    public function visible(string $placement): Collection
    {
        return WebsiteTechnology::active()->where($placement === 'homepage' ? 'show_on_homepage' : 'show_in_filters', true)
            ->whereHas('services', fn ($query) => $query->where('website_services.status', true))->get();
    }

    public function serialize(WebsiteTechnology $technology): array
    {
        return ['id' => $technology->id, 'name' => $technology->name, 'slug' => $technology->slug,
            'url' => '/service/info?technology='.$technology->slug];
    }

    public function resolve(string $value, string $type = 'slug'): ?WebsiteTechnology
    {
        $value = $type === 'name' ? mb_strtolower(trim($value)) : $value;
        $technology = $type === 'name'
            ? WebsiteTechnology::whereRaw('LOWER(name) = ?', [$value])->first()
            : ($type === 'slug' ? WebsiteTechnology::where('slug', $value)->first() : null);
        if ($technology) {
            return $technology;
        }
        $id = DB::table('website_technology_aliases')->where('type', $type)->where('value', $value)->value('website_technology_id');

        return $id ? WebsiteTechnology::find($id) : null;
    }

    public function alias(WebsiteTechnology $technology, string $type, string $value): void
    {
        $value = $type === 'name' ? mb_strtolower(trim($value)) : trim($value);
        if ($value === '') {
            return;
        }
        $existing = DB::table('website_technology_aliases')->where('type', $type)->where('value', $value)->first();
        if ($existing && (int) $existing->website_technology_id !== $technology->id) {
            throw ValidationException::withMessages([$type === 'name' ? 'name' : 'slug' => 'This value is reserved by another technology.']);
        }
        $canonical = $type === 'name' ? WebsiteTechnology::whereRaw('LOWER(name) = ?', [$value])->first()
            : ($type === 'slug' ? WebsiteTechnology::where('slug', $value)->first() : null);
        if ($canonical && $canonical->id !== $technology->id) {
            throw ValidationException::withMessages([$type === 'name' ? 'name' : 'slug' => 'This value belongs to another technology.']);
        }
        DB::table('website_technology_aliases')->updateOrInsert(['type' => $type, 'value' => $value], ['website_technology_id' => $technology->id]);
    }

    public function update(WebsiteTechnology $technology, array $attributes): void
    {
        DB::transaction(function () use ($technology, $attributes) {
            $technology = WebsiteTechnology::whereKey($technology->id)->lockForUpdate()->firstOrFail();
            $name = $technology->name;
            $slug = $technology->slug;
            $technology->update($attributes);
            if ($name !== $technology->name) {
                $this->alias($technology, 'name', $name);
            }
            if ($slug !== $technology->slug) {
                $this->alias($technology, 'slug', $slug);
            }
        });
    }

    public function merge(WebsiteTechnology $source, WebsiteTechnology $target): void
    {
        if ($source->id === $target->id) {
            throw ValidationException::withMessages(['target_id' => 'Choose a different target technology.']);
        }
        DB::transaction(function () use ($source, $target) {
            $locked = WebsiteTechnology::whereIn('id', [$source->id, $target->id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            abort_unless($locked->count() === 2, 404);
            $source = $locked[$source->id];
            $target = $locked[$target->id];
            $target->services()->syncWithoutDetaching($source->services()->pluck('website_services.id')->all());
            DB::table('website_technology_aliases')->where('website_technology_id', $source->id)->update(['website_technology_id' => $target->id]);
            $name = $source->name;
            $slug = $source->slug;
            $source->delete();
            $this->alias($target, 'name', $name);
            $this->alias($target, 'slug', $slug);
        });
    }
}
