<?php

namespace App\Exceptions;

use Exception;

class SlotUnavailableException extends Exception
{
    protected $message = 'Este horario ya no está disponible.';
}