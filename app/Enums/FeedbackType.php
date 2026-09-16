<?php

namespace App\Enums;

enum FeedbackType: string
{
    case Suggestion = 'suggestion';
    case Incident = 'incident';

    public function label(): string
    {
        return match($this) {
            self::Suggestion => 'Sugerencia',
            self::Incident => 'Incidencia',
        };
    }

    /**
     * Indica el color que tendrá el badge de la celda en la tabla en función del tipo de feedback
     */
    public function badgeColor(): string
    {
        return match($this) {
            self::Suggestion => 'blue',
            self::Incident => 'red',
        };
    }

    /**
     * Indica el icono que tendrá el badge de la celda en la tabla en función del tipo de feedback
     */
    public function badgeIcon(): string
    {
        return match($this) {
            self::Suggestion => 'ti ti-bulb',
            self::Incident => 'ti ti-alert-triangle',
        };
    }
}