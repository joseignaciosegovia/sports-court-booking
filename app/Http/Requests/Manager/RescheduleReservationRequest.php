<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleReservationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'start' => 'required|date',
	        'end' => 'required|date|after:start',
        ];
    }
}