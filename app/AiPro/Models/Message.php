<?php

namespace App\AiPro\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'ai_pro_messages';

    protected $guarded = ['id'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['sources' => 'array', 'usage' => 'array'];
    }
}
