<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'menu_id',
        'fa_icon',
        'order_number',
        'status',
    ];

    /**
     * Get the menu for the Module.
     */
    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * Get the links for the module.
     */
    public function links()
    {
        return $this->hasMany(Link::class);
    }
}
