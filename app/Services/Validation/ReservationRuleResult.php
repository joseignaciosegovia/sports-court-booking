<?php

namespace App\Services\Validation;

class ReservationRuleResult
{
    private function __construct(
        public readonly bool $success,
        public readonly ?string $message = null,
        public readonly ?string $field = null,
    ) {
    }

    public static function success(): self
    {
        return new self(success: true);
    }

    public static function failure(string $message, ?string $field = null): self
    {
        return new self(success: false, message: $message, field: $field);
    }
}