<?php

namespace App\AiPro\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $table = 'ai_pro_sites';

    protected $guarded = ['id'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['origins' => 'array', 'enabled' => 'boolean', 'is_local' => 'boolean', 'resync_requested' => 'boolean'];
    }
}
