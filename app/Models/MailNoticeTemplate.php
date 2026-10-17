<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailNoticeTemplate extends Model
{
    protected $fillable = ['name', 'subject', 'body'];
}
