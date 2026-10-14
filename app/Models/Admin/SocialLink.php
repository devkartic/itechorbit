<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'fa_icon',
        'social_url',
        'status',
        'order_number',
    ];

    /**
     * Get the user for the social links
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
