<?php

namespace App\Services\Manager;

use App\Mail\ReservationRescheduledMail;
use App\Models\Reservation;
use App\Services\Validation\ReservationRuleResult;
use App\Services\Validation\ReservationRulesValidator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class ReservationRescheduleService
{
    public function __construct(
        private readonly ReservationRulesValidator $rules,
    ) {
    }

    public function reschedule(Reservation $reservation, array $data): ReservationRuleResult
    {
        if (in_array($reservation->payment_status, ['canceled', 'refunded'])) {
            return ReservationRuleResult::failure(
                'No se puede reprogramar una reserva cancelada o reembolsada.'
            );
        }

        $newStart = Carbon::parse($data['start']);
        $newEnd = Carbon::parse($data['end']);

        if ($newStart->isPast()) {
            return ReservationRuleResult::failure(
                'No se puede mover una reserva a una fecha pasada.'
            );
        }

        if ($failure = $this->rules->checkWithinOpeningHours($newStart, $newEnd)) {
            return $failure;
        }

        if ($failure = $this->rules->checkClientRestrictions($reservation, $newStart, $newEnd)) {
            return $failure;
        }

        if ($failure = $this->rules->checkOverlap($reservation->court_id, $newStart, $newEnd)) {
            return $failure;
        }

        $oldStartTime = $reservation->start_time->copy();

        $reservation->update([
            'start_time' => $newStart,
            'end_time' => $newEnd,
        ]);

        // Si la reserva la hizo un cliente, se le envía un email informándole de la modificación de la fecha
        if ($reservation->user_id && $reservation->user) {
            Mail::to($reservation->user->email)
                ->queue(new ReservationRescheduledMail($reservation, $oldStartTime));
        }

        return ReservationRuleResult::success();
    }
}