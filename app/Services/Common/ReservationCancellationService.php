<?php

namespace App\Services\Common;

use App\Exceptions\RefundException;
use App\Exceptions\ReservationNotCancellableException;
use App\Mail\ReservationCanceledByManagerMail;
use App\Models\Reservation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Refund;
use Stripe\Stripe;
use App\Enums\PaymentStatus;
use App\Enums\CanceledBy;
use Illuminate\Support\Facades\DB;

class ReservationCancellationService
{
    /**
     * Cancelación iniciada por el propio cliente.
     * Solo hay devolución si quedan más de 12h para la reserva.
     */
    public function cancelByClient(Reservation $reservation): string
    {
        return DB::transaction(function () use ($reservation) {
            // Releemos la fila bloqueada: el modelo recibido puede estar desfasado
            $reservation = Reservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            if (! $reservation->payment_status->canBeCanceled()) {
                throw new ReservationNotCancellableException();
            }

            // Si nunca hubo pago real, no hay nada que reembolsar
            if ($reservation->payment_status !== PaymentStatus::Paid) {
                $reservation->update([
                    'payment_status' => PaymentStatus::Canceled,
                    'canceled_at'    => now(),
                    'canceled_by'    => CanceledBy::Client,
                ]);

                return 'canceled_no_payment';
            }

            if ($reservation->start_time->gt(now()->addHours(12))) {
                $this->issueRefund($reservation);

                $reservation->update([
                    'payment_status' => PaymentStatus::Refunded,
                    'canceled_at'    => now(),
                    'canceled_by'    => CanceledBy::Client,
                    'refunded_at'    => now(),
                ]);

                return 'refunded';
            }

            $reservation->update([
                'payment_status' => PaymentStatus::Canceled,
                'canceled_at'    => now(),
                'canceled_by'    => CanceledBy::Client,
            ]);

            return 'canceled_late';
        });
    }

    /**
     * Cancelación iniciada por manager/admin. Siempre hay devolución,
     * porque la decisión no fue del cliente.
     */
    public function cancelByManager(Reservation $reservation, string $reason): bool
    {
        $hadRealPayment = DB::transaction(function () use (&$reservation, $reason) {
            $reservation = Reservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            if (! $reservation->payment_status->canBeCanceled()) {
                throw new ReservationNotCancellableException();
            }

            $hadRealPayment = $reservation->payment_status === PaymentStatus::Paid && $reservation->user_id;

            if ($hadRealPayment) {
                $this->issueRefund($reservation);

                $reservation->update([
                    'payment_status'      => PaymentStatus::Refunded,
                    'canceled_at'         => now(),
                    'canceled_by'         => CanceledBy::Manager,
                    'cancellation_reason' => $reason,
                    'refunded_at'         => now(),
                ]);
            } else {
                $reservation->update([
                    'payment_status'      => PaymentStatus::Canceled,
                    'canceled_at'         => now(),
                    'canceled_by'         => CanceledBy::Manager,
                    'cancellation_reason' => $reason,
                ]);
            }

            return (bool) $hadRealPayment;
        });

        // Fuera de la transacción: el job no debe ejecutarse antes del commit
        if ($reservation->user_id && $reservation->user) {
            Mail::to($reservation->user->email)
                ->queue(new ReservationCanceledByManagerMail($reservation, $reason));
        }

        return $hadRealPayment;
    }

    public function cancelExpiredReservations(): int
    {
        return Reservation::query()
            ->where('payment_status', PaymentStatus::Pending)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update([
                'payment_status' => PaymentStatus::Canceled,
                'canceled_at' => now(),
                'canceled_by' => CanceledBy::System->value,
            ]);
    }

    private function issueRefund(Reservation $reservation): void
    {
        if (!$reservation->payment_id) {
            Log::warning('Intento de reembolso sin payment_id', ['reservation_id' => $reservation->id]);
            throw new RefundException('No se puede realizar el reembolso.');
        }

        try {
            $refund = $this->createRefund($reservation);
            $reservation->stripe_refund_id = $refund->id;
        } catch (\Exception $e) {
            Log::error('Error al crear reembolso en Stripe: ' . $e->getMessage(), [
                'reservation_id' => $reservation->id,
            ]);
            throw new RefundException('No se pudo procesar la devolución. Inténtelo de nuevo o contacta con soporte.');
        }
    }

    protected function createRefund(Reservation $reservation): Refund
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        return Refund::create(
            ['payment_intent' => $reservation->payment_id],
            ['idempotency_key' => 'refund-reservation-' . $reservation->id]
        );
    }
}