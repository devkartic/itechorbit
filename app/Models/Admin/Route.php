<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'fa_icon',
        'order_number',
        'status',
    ];

    /**
     * Get the links for the route.
     */
    public function links()
    {
        return $this->hasMany(Link::class);
    }
}
