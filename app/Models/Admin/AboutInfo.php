<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AboutInfo extends Model
{
    use HasFactory;

    protected $table = 'website_about';

    protected $fillable = [
        'title',
        'subtitle',
        'descriptions',
        'status',
    ];
}
