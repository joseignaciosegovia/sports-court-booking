<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\FeedbackType;
use Illuminate\Validation\Rule;

class StoreFeedbackRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:255'],
            'type'    => ['required', Rule::enum(FeedbackType::class)],
        ];
    }
}