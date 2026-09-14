<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'content' => 'required|string|max:255',
	        'type' => 'required',
        ];
    }
}