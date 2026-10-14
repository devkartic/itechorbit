<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'project_category',
        'client_name',
        'skill_id',
        'jobs',
        'technology_used',
        'played_role',
        'about_project',
        'project_url',
        'image_path',
        'status',
        'order_number',
    ];

    /**
     * Get the user for the project
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the technology/skill for the project
     */
    public function skill(): object
    {
        return $this->belongsTo(Skill::class);
    }
}
