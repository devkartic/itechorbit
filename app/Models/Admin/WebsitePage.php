<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class WebsitePage extends Model
{
    protected $fillable = ['page_key', 'heading', 'introduction', 'cta_label', 'cta_url', 'meta_title', 'meta_description', 'share_image', 'status'];
}
