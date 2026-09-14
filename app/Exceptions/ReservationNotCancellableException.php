<?php

namespace App\Exceptions;

use Exception;

class ReservationNotCancellableException extends Exception
{
    protected $message = 'Esta reserva ya no se puede cancelar.';
}