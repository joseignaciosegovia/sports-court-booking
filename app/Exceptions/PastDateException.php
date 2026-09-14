<?php

namespace App\Exceptions;

use Exception;

class PastDateException extends Exception
{
    protected $message = 'No se puede crear una reserva en una fecha pasada.';
}