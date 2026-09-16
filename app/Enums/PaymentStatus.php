<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Paid = 'paid';
    case Pending = 'pending';
    case Canceled = 'canceled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::Paid => 'Pagada',
            self::Pending => 'Pendiente de pago',
            self::Canceled => 'Cancelada',
            self::Refunded => 'Reembolsada',
        };
    }

    /**
     * Indica el color que tendrá el badge de la celda en la tabla en función del estado de pago
     */

    public function badgeColor(): string
    {
        return match($this) {
            self::Paid => 'green',
            self::Pending => 'blue',
            self::Canceled => 'red',
            self::Refunded => 'purple',
        };
    }

    /**
     * Indica el icono que tendrá el badge de la celda en la tabla en función del estado de pago
     */

    public function badgeIcon(): string
    {
        return match($this) {
            self::Paid => 'ti ti-circle-check',
            self::Pending => 'ti ti-clock',
            self::Canceled => 'ti ti-ban',
            self::Refunded => 'ti ti-receipt-refund',
        };
    }

    /**
     * Indica si la reserva está en un estado de cancelación.
     */
    public function isCanceled(): bool
    {
        return match ($this) {
            self::Canceled, self::Refunded => true,
            default => false,
        };
    }

    /**
     * Indica si el usuario puede continuar con el pago.
     */
    public function isPayable(): bool
    {
        return $this === self::Pending;
    }

    /**
     * Indica si la reserva puede ser cancelada
     * @return bool
     */
    public function canBeCanceled(): bool
    {
        return match ($this) {
            self::Paid, self::Pending => true,
            default => false,
        };
    }
}