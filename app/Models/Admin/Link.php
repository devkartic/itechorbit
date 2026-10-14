<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'module_id',
        'url',
        'fa_icon',
        'order_number',
        'status',
    ];

    /**
     * Get the module for the link.
     */
    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Get the permission for the link.
     */
    public function permission()
    {
        return $this->hasMany(Permission::class);
    }
}
