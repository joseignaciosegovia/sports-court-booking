<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourtRequest extends FormRequest
{
    public function rules(): array
    {
        // Guardamos la pista que estamos modificando
        $court = $this->route('court');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                // No puede haber una pista con el mismo nombre en la misma localización
                // No tenemos en cuentra la propia pista que se está modificando
                Rule::unique('courts')
                    ->where(
                        fn ($query) => $query->where('location', $this->location)
                    )
                    ->ignore($court->id),
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