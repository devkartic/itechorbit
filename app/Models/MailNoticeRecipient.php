<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailNoticeRecipient extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['generation' => 'integer', 'attempts' => 'integer', 'dispatched_at' => 'datetime', 'sending_at' => 'datetime', 'sent_at' => 'datetime'];
    }
}
