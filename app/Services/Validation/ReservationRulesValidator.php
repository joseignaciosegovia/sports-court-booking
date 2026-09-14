<?php

namespace App\Services\Validation;

use App\Models\Reservation;
use App\Services\Validation\ReservationRuleResult;
use Carbon\Carbon;

class ReservationRulesValidator
{
    public function checkWithinOpeningHours(Carbon $start, Carbon $end): ?ReservationRuleResult
    {
        $openingTime = config('schedules.opening_time');
        $closingTime = config('schedules.closing_time');

        $opening = Carbon::createFromFormat('H:i', $openingTime);
        $closing = Carbon::createFromFormat('H:i', $closingTime);

        $openingMinutes = $opening->hour * 60 + $opening->minute;
        $closingMinutes = $closing->hour * 60 + $closing->minute;
        $startMinutes = $start->hour * 60 + $start->minute;
        $endMinutes = $end->hour * 60 + $end->minute;

        if ($startMinutes < $openingMinutes || $endMinutes > $closingMinutes) {
            return ReservationRuleResult::failure(
                "La reserva debe estar dentro del horario de apertura ({$openingTime}) y cierre ({$closingTime}).",
                'start_time_only'
            );
        }

        return null;
    }

    public function checkClientRestrictions(Reservation $reservation, Carbon $start, Carbon $end): ?ReservationRuleResult
    {
        if (!$reservation->user_id) {
            return null;
        }

        if ($start->minute !== 0) {
            return ReservationRuleResult::failure(
                'Las reservas realizadas por clientes deben comenzar en una hora redonda (por ejemplo, 09:00, 10:00, etc.).',
                'start_time_only'
            );
        }

        if ($end->minute !== 0) {
            return ReservationRuleResult::failure(
                'Las reservas realizadas por clientes deben finalizar en una hora redonda (por ejemplo, 10:00, 11:00, etc.).',
                'end_time_only'
            );
        }

        if ($start->diffInMinutes($end) != 60) {
            return ReservationRuleResult::failure(
                'Las reservas realizadas por clientes deben tener una duración exacta de una hora.',
                'end_time_only'
            );
        }

        return null;
    }

    public function checkOverlap(int $courtId, Carbon $start, Carbon $end, ?int $excludeReservationId = null): ?ReservationRuleResult
    {
        $overlap = Reservation::where('court_id', $courtId)
            ->when($excludeReservationId, fn ($q) => $q->where('id', '!=', $excludeReservationId))
            ->blocking() // Función del modelo Reservation que devuelve los horarios ocupados
            ->where(function ($q) use ($start, $end) {
                $q->where('start_time', '<', $end)
                    ->where('end_time', '>', $start);
            })
            ->exists();

        if ($overlap) {
            return ReservationRuleResult::failure(
                'Ya existe otra reserva que se solapa con este horario en esta pista.',
                'start_time_only'
            );
        }

        return null;
    }
}