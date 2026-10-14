<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'fa_icon',
        'status',
        'order_number',
    ];

    /**
     * Get the user for the skill
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the project for the technology/skill
     */
    public function projects(): object
    {
        return $this->hasMany(Project::class);
    }

    public static function skills_to_json($skills = null)
    {
        if (empty($skills)) {
            return $skills;
        }
        $project_skills = [];
        foreach ($skills as $skill) {
            $find_skill = Skill::find($skill);
            if ($find_skill) {
                $project_skills[] = Skill::find($skill)->name;
            }
        }

        return json_encode($project_skills);
    }
}
