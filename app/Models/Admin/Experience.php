<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'fa_icon',
        'designation',
        'organization',
        'responsibilities',
        'descriptions',
        'join_date',
        'resign_date',
        'status',
        'order_number',
    ];

    /**
     * Get the user for the social experiences.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
