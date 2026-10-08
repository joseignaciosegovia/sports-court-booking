<?php

namespace App\Http\Requests\Common;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ValidDni;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'current_password' => ['required_with:password', 'nullable', 'current_password'],
            'dni' => ['required', 'string', new ValidDni, Rule::unique('users', 'dni')->ignore($this->user()->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'photo' => ['nullable', 'image', 'max:4096'],
            'delete_photo' => 'nullable|boolean',
        ];
    }
}