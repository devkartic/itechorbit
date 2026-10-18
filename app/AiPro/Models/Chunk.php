<?php

namespace App\AiPro\Models;

use Illuminate\Database\Eloquent\Model;

class Chunk extends Model
{
    protected $table = 'ai_pro_chunks';

    protected $guarded = ['id'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['embedding' => 'array'];
    }
}
