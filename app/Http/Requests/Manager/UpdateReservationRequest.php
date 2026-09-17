<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Reservation;

class UpdateReservationRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Reservation $reservation */
        $reservation = $this->route('reservation');

        $rules = [
            'date' => 'required|date',
            'start_time_only' => 'required|date_format:H:i',
            'end_time_only' => 'required|date_format:H:i|after:start_time_only',
        ];

        if (!$reservation->user_id) {
            $rules['information'] = 'required|string|max:500';
        }

        return $rules;
    }
}