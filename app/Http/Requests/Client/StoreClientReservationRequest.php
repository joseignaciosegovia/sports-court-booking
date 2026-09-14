<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientReservationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'court_id' => 'required|exists:courts,id',
	        'start_time' => 'required|date',
        ];
    }
}