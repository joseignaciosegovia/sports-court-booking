<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourtRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'reservation_price' => 'required|numeric|min:0',
            'location' => 'required|string|max:255',
        ];
    }
}