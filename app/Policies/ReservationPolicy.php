<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    public function cancel(User $user, Reservation $reservation): bool
    {
        return $reservation->user_id === $user->id;
    }
}