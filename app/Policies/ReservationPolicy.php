<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    // ¿El usuario que intenta cancelar una reserva es el que realizó esa reserva?
    public function cancel(User $user, Reservation $reservation): bool
    {
        return $reservation->user_id === $user->id;
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return $user->id === $reservation->user_id;
    }

    public function resume(User $user, Reservation $reservation): bool
    {
        return $user->id === $reservation->user_id;
    }
}