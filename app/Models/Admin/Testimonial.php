<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'designation',
        'organization',
        'mobile',
        'email',
        'comment',
        'ratings',
        'status',
        'order_number',
    ];

    /**
     * Get the user for testimonials
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
