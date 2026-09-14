<?php

namespace App\Services\Manager;

use App\Models\Reservation;
use App\Services\Validation\ReservationRuleResult;
use App\Services\Validation\ReservationRulesValidator;
use Carbon\Carbon;

class ReservationCreateService
{
    public function __construct(
        private readonly ReservationRulesValidator $rules,
    ) {
    }

    public function create(array $data): ReservationRuleResult
    {
        $startTime = Carbon::parse($data['date'] . ' ' . $data['start_time_only']);
        $endTime = Carbon::parse($data['date'] . ' ' . $data['end_time_only']);

        if ($failure = $this->rules->checkOverlap($data['court_id'], $startTime, $endTime)) {
            return $failure;
        }

        Reservation::create([
            'court_id' => $data['court_id'],
            'user_id' => null,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'information' => $data['information'] ?? null,
            'payment_status' => 'paid', // reserva interna, sin pago real: se fuerza a "paid" para que bloquee el horario
        ]);

        return ReservationRuleResult::success();
    }
}