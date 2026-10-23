<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class WebsiteProductRequest extends WebsiteServiceRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        if (is_string($this->input('technologies'))) {
            $this->merge(['technologies' => array_values(array_filter(array_map('trim', explode("\n", $this->input('technologies')))))]);
        }
    }

    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['technology_ids'], $rules['technology_ids.*']);
        $rules['technologies'] = ['nullable', 'array', 'max:50'];
        $rules['technologies.*'] = ['required', 'string', 'max:100'];
        $rules['slug'] = ['required', 'string', 'max:180', 'regex:/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/D', Rule::notIn(['create']), Rule::unique('website_products')->ignore($this->route('product_info')?->id), Rule::unique('website_services')];
        $rules['price'] = ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'];
        $rules['currency'] = ['required_with:price', 'nullable', 'string', 'regex:/^[A-Z]{3}$/D'];
        $rules['delivery_time'] = ['nullable', 'string', 'max:255'];

        return $rules;
    }
}
