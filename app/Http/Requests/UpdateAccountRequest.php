<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->status;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users')->ignore($this->user()->id)],
            'current_password' => [Rule::requiredIf($this->input('email') !== $this->user()->email), 'nullable', 'current_password:web'],
        ];
    }
}
