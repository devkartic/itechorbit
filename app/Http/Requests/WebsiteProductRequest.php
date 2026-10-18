<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class WebsiteProductRequest extends WebsiteServiceRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['slug'] = ['required', 'string', 'max:180', 'regex:/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/D', Rule::notIn(['create']), Rule::unique('website_products')->ignore($this->route('product_info')?->id), Rule::unique('website_services')];
        $rules['price'] = ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'];
        $rules['currency'] = ['required_with:price', 'nullable', 'string', 'regex:/^[A-Z]{3}$/D'];
        $rules['delivery_time'] = ['nullable', 'string', 'max:255'];

        return $rules;
    }
}
