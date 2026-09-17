<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;

class StoreManagerReservationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'court_id' => 'required|exists:courts,id',
            'date' => 'required|date',
            'start_time_only' => 'required|date_format:H:i',
            'end_time_only' => 'required|date_format:H:i|after:start_time_only',
            'information' => 'required|string|max:500',
        ];
    }
}