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
        'website_url',
        'email',
        'phone',
        'mobile',
        'address',
    ];

    protected $hidden = ['legacy_contact_details_backup'];

    public function publicDetails(): array
    {
        $details = [];
        foreach (['website_url' => 'Website', 'email' => 'Email', 'phone' => 'Phone', 'mobile' => 'Mobile', 'address' => 'Address'] as $field => $label) {
            $value = trim((string) $this->{$field});
            if ($value === '') {
                continue;
            }
            $href = match ($field) {
                'website_url' => filter_var($value, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($value, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true) ? $value : null,
                'email' => filter_var($value, FILTER_VALIDATE_EMAIL) ? 'mailto:'.$value : null,
                'phone', 'mobile' => preg_match('/^\+?[0-9 () .-]+$/', $value) && preg_match('/\d/', $value) ? 'tel:'.preg_replace('/[^+0-9]/', '', $value) : null,
                default => null,
            };
            $details[] = ['label' => $label, 'value' => $value, 'href' => $href];
        }

        return $details;
    }
}
