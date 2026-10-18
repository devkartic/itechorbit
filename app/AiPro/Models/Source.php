<?php

namespace App\AiPro\Models;

use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    protected $table = 'ai_pro_sources';

    protected $guarded = ['id'];

    protected $attributes = ['enabled' => true, 'status' => 'pending'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }
}
