<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactInfo extends Model
{
    use HasFactory;

    protected $table = 'website_contact';

    protected $fillable = [
        'title',
        'subtitle',
        'descriptions',
        'status',
    ];
}
