<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteProduct extends Model
{
    use HasFactory;

    protected $table = 'website_products';

    protected $fillable = [
        'title',
        'subtitle',
        'descriptions',
        'status',
        'price', 'currency', 'delivery_time',
        'slug', 'short_title', 'image_path', 'display_order', 'icon', 'highlight',
        'problem', 'solution', 'deliverables', 'technologies', 'approach', 'use_cases',
    ];

    protected function casts(): array
    {
        return ['status' => 'boolean', 'highlight' => 'boolean', 'display_order' => 'integer',
            'deliverables' => 'array', 'technologies' => 'array', 'approach' => 'array', 'use_cases' => 'array', 'price' => 'decimal:2'];
    }

    public function scopePublished($query)
    {
        return $query->where('status', 1)->orderBy('display_order')->orderBy('id');
    }
}
