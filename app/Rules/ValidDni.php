<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Closure;

class ValidDni implements ValidationRule
{
    private const LETTERS = 'TRWAGMYFPDXBNJZSQVHLCKE';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/^(\d{8})([A-Za-z])$/', $value, $matches)) {
            $fail('El DNI debe tener 8 dígitos seguidos de una letra.');
            return;
        }

        $number = (int) $matches[1];
        $letter = strtoupper($matches[2]);
        $expected = self::LETTERS[$number % 23];

        if ($letter !== $expected) {
            $fail('La letra del DNI no es válida para ese número.');
        }
    }
}