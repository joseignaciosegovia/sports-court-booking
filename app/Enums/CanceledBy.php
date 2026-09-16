<?php

namespace App\Enums;

enum CanceledBy: string
{
    case Manager = 'manager';
    case Client = 'client';
    case System = 'system';

    public function label(): string
    {
        return match($this) {
            self::Manager => 'Cancelada por Gestión',
            self::Client => 'Cancelada por el Cliente',
            self::System => 'Expiración automática',
        };
    }
}