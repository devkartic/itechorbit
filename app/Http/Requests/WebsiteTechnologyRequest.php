<?php

namespace App\Http\Requests;

use App\Models\Admin\WebsiteTechnology;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WebsiteTechnologyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => trim((string) $this->input('name')), 'slug' => trim((string) $this->input('slug'))]);
        foreach (['is_checked', 'show_on_homepage', 'show_in_filters'] as $field) {
            if (in_array($this->input($field), ['true', 'false'], true)) {
                $this->merge([$field => $this->input($field) === 'true']);
            }
        }
    }

    public function rules(): array
    {
        $id = $this->route('website_technology')?->id;

        $aliasRule = fn ($type) => function ($attribute, $value, $fail) use ($id, $type) {
            $value = $type === 'name' ? mb_strtolower($value) : $value;
            if (\Illuminate\Support\Facades\DB::table('website_technology_aliases')->where('type', $type)->where('value', $value)
                ->when($id, fn ($query) => $query->where('website_technology_id', '!=', $id))->exists()) {
                $fail('This value is reserved by another technology.');
            }
        };

        return [
            'name' => ['required', 'string', 'max:100', $aliasRule('name'), Rule::unique('website_technologies')->ignore($id), function ($attribute, $value, $fail) use ($id) {
                if (WebsiteTechnology::whereRaw('LOWER(name) = ?', [mb_strtolower($value)])->when($id, fn ($query) => $query->where('id', '!=', $id))->exists()) {
                    $fail('A technology with this name already exists.');
                }
            }],
            'slug' => ['required', 'string', 'max:180', $aliasRule('slug'), 'regex:/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/D', Rule::notIn(['create']), Rule::unique('website_technologies')->ignore($id)],
            'show_in_filters' => ['sometimes', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'is_checked' => ['sometimes', 'boolean'], 'show_on_homepage' => ['sometimes', 'boolean'],
        ];
    }
}
