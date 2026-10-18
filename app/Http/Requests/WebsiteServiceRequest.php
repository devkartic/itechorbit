<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WebsiteServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The existing ServiceInfoController middleware checks action-specific permissions.
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        foreach (['is_checked', 'highlight', 'remove_image'] as $field) {
            if (in_array($this->input($field), ['true', 'false'], true)) {
                $this->merge([$field => $this->input($field) === 'true']);
            }
        }
        foreach (['deliverables', 'technologies', 'approach', 'use_cases'] as $field) {
            if (is_string($this->input($field))) {
                $lines = array_values(array_filter(array_map('trim', explode("\n", $this->input($field))), fn ($line) => $line !== ''));
                if ($field === 'use_cases') {
                    $lines = array_map(function ($line) {
                        $parts = explode('|', $line, 2);

                        return ['title' => trim($parts[0]), 'description' => trim($parts[1] ?? '')];
                    }, $lines);
                }
                $this->merge([$field => $lines]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:180', 'regex:/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/D', Rule::notIn(['create']), Rule::unique('website_services')->ignore($this->route('service_info')?->id), Rule::unique('website_products')],
            'service_type' => ['prohibited'],
            'short_title' => ['nullable', 'string', 'max:100'],
            'subtitle' => ['required', 'string', 'max:255'],
            'descriptions' => ['required', 'string', 'max:60000', function ($attribute, $value, $fail) {
                if (is_string($value) && strlen($value) > 60000) {
                    $fail('The description must not exceed 60,000 bytes.');
                }
            }],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'icon' => ['nullable', Rule::in(['code', 'layers', 'refresh', 'network', 'building', 'database', 'gauge', 'shield', 'sparkles'])],
            'is_checked' => ['sometimes', 'boolean'], 'highlight' => ['sometimes', 'boolean'],
            'uploaded_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['sometimes', 'boolean'],
            'problem' => ['nullable', 'string', 'max:10000'], 'solution' => ['nullable', 'string', 'max:10000'],
            'deliverables' => ['nullable', 'array', 'max:50'], 'deliverables.*' => ['required', 'string', 'max:1000'],
            'technologies' => ['nullable', 'array', 'max:50'], 'technologies.*' => ['required', 'string', 'max:100'],
            'approach' => ['nullable', 'array', 'max:50'], 'approach.*' => ['required', 'string', 'max:1000'],
            'use_cases' => ['nullable', 'array', 'max:30'], 'use_cases.*' => ['array:title,description'],
            'use_cases.*.title' => ['required', 'string', 'max:255'], 'use_cases.*.description' => ['required', 'string', 'max:2000'],
            'price' => ['prohibited'], 'currency' => ['prohibited'], 'delivery_time' => ['prohibited'],
        ];
    }
}
