<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateManagerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($this->manager->id),
            ],

            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],

            'dni' => [
                'required', 
                'string', 
                'max:20',
                Rule::unique('users', 'dni')->ignore($this->manager->id)
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'in:manager,admin'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ];
    }
}