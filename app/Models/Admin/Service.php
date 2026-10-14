<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'fa_icon',
        'title',
        'about_service',
        'service_url',
        'status',
        'order_number',
    ];

    /**
     * Get the user for the services
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
