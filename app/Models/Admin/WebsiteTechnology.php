<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WebsiteTechnology extends Model
{
    protected $fillable = ['name', 'slug', 'status', 'show_on_homepage', 'show_in_filters', 'display_order'];

    protected function casts(): array
    {
        return ['status' => 'boolean', 'show_on_homepage' => 'boolean', 'show_in_filters' => 'boolean', 'display_order' => 'integer'];
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(ServiceInfo::class, 'website_service_technology', 'website_technology_id', 'website_service_id')->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('website_technologies.status', true)->orderBy('website_technologies.display_order')->orderBy('website_technologies.name');
    }
}
