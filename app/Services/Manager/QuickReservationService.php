<?php

namespace App\Services\Manager;

use App\Models\Court;
use App\Models\Reservation;
use App\Exceptions\SlotUnavailableException;
use App\Services\Validation\ReservationRulesValidator;
use App\Exceptions\PastDateException;
use Carbon\Carbon;
use App\Enums\PaymentStatus;

class QuickReservationService
{
    public function __construct(
        private readonly ReservationRulesValidator $rules,
    ) {
    }

    public function create(Court $court, array $data): Reservation
    {
        $startTime = Carbon::parse($data['start']);
        $endTime = Carbon::parse($data['end']);

        if ($startTime->isPast()) {
            throw new PastDateException();
        }

        if ($this->rules->checkOverlap($court->id, $startTime, $endTime)) {
            throw new SlotUnavailableException();
        }

        return Reservation::create([
            'court_id' => $court->id,
            'user_id' => null,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'information' => $data['information'] ?? null,
            'payment_status' => PaymentStatus::Paid,
        ]);
    }
}