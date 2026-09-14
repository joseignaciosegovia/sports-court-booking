<?php

namespace App\Exceptions;

use Exception;

class ReservationNotResumableException extends Exception
{
    protected $message = 'Esta reserva ya no se puede pagar. Es posible que haya expirado.';
}