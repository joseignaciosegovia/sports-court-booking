<?php

namespace App\Services\Manager;

use App\Models\Reservation;
use App\Services\Validation\ReservationRuleResult;
use App\Services\Validation\ReservationRulesValidator;
use Carbon\Carbon;
use App\Enums\PaymentStatus;

class ReservationUpdateService
{
    public function __construct(
        private readonly ReservationRulesValidator $rules,
    ) {
    }

    public function update(Reservation $reservation, array $data): ReservationRuleResult
    {
        if (in_array($reservation->payment_status, [PaymentStatus::Canceled, PaymentStatus::Refunded])) {
            return ReservationRuleResult::failure(
                'No se puede editar una reserva cancelada o reembolsada.',
                'reservation'
            );
        }

        if ($reservation->start_time->isPast()) {
            return ReservationRuleResult::failure(
                'No se puede editar una reserva que ya ha tenido lugar.',
                'reservation'
            );
        }

        $startTime = Carbon::parse($data['date'] . ' ' . $data['start_time_only']);
        $endTime = Carbon::parse($data['date'] . ' ' . $data['end_time_only']);

        if ($startTime->isPast()) {
            return ReservationRuleResult::failure(
                'No se puede establecer una reserva en una fecha pasada.',
                'start_time_only'
            );
        }

        if ($failure = $this->rules->checkWithinOpeningHours($startTime, $endTime)) {
            return $failure;
        }

        if ($failure = $this->rules->checkClientRestrictions($reservation, $startTime, $endTime)) {
            return $failure;
        }

        if ($failure = $this->rules->checkOverlap($reservation->court_id, $startTime, $endTime)) {
            return $failure;
        }

        $updateData = [
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];

        if (!$reservation->user_id) {
            $updateData['information'] = $data['information'] ?? null;
        }

        $reservation->update($updateData);

        return ReservationRuleResult::success();
    }
}