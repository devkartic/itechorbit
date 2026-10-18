<?php

namespace App\AiPro\Models;

use Illuminate\Database\Eloquent\Model;

class SyncRun extends Model
{
    protected $table = 'ai_pro_sync_runs';

    protected $guarded = ['id'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['errors' => 'array', 'finished_at' => 'datetime'];
    }
}
