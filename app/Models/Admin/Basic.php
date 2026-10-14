<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Basic extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'designation',
        'mobile',
        'address',
        'service_offering_title',
        'career_objective',
        'self_introduction',
        'additional_information',
        'banner_image_path',
        'profile_image_path',
        'cv_path',
        'cv_public_status',
        'experience_year',
        'birth_date',
        'gender',
        'marital_status',
        'status',
    ];

    /**
     * Get the user for the basic.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
