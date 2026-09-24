<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourtRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                // No puede haber una pista con el mismo nombre en la misma localización
                Rule::unique('courts')->where(
                    fn ($query) => $query->where('location', $this->location)
                ),
            ],
            'reservation_price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'location' => [
                'required',
                'string',
                'max:100',
            ],
        ];
    }
}