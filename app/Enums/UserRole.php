<?php

namespace App\Enums;

enum UserRole: string
{
    case Client = 'client';
    case Manager = 'manager';
    case Admin = 'admin';

    public function label(): string
    {
        return match($this) {
            self::Client => 'Cliente',
            self::Manager => 'Gestor',
            self::Admin => 'Administrador',
        };
    }

    /**
     * Indica el color que tendrá el badge de la celda en la tabla en función del tipo de usuario
     */
    public function badgeColor(): string
    {
        return match($this) {
            self::Manager => 'blue',
            self::Admin => 'purple',
        };
    }
}