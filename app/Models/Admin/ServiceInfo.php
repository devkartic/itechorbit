<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ServiceInfo extends Model
{
    use HasFactory;

    protected $table = 'website_services';

    protected $fillable = [
        'title',
        'subtitle',
        'descriptions',
        'status',
        'slug', 'short_title', 'image_path', 'display_order', 'icon', 'highlight',
        'problem', 'solution', 'deliverables', 'technologies', 'approach', 'use_cases',
    ];

    protected function casts(): array
    {
        return ['status' => 'boolean', 'highlight' => 'boolean', 'display_order' => 'integer',
            'deliverables' => 'array', 'technologies' => 'array', 'approach' => 'array', 'use_cases' => 'array'];
    }

    public function scopePublished($query)
    {
        return $query->where('status', 1)->orderBy('display_order')->orderBy('id');
    }

    public function websiteTechnologies(): BelongsToMany
    {
        return $this->belongsToMany(WebsiteTechnology::class, 'website_service_technology', 'website_service_id', 'website_technology_id')->withTimestamps();
    }
}
