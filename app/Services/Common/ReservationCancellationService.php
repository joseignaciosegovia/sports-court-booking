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

class ReservationCancellationService
{
    /**
     * Cancelación iniciada por el propio cliente.
     * Solo hay devolución si quedan más de 12h para la reserva.
     */
    public function cancelByClient(Reservation $reservation): string
    {
        // Si nunca hubo pago real, no hay nada que reembolsar
        if ($reservation->payment_status !== PaymentStatus::Paid) {
            $reservation->update([
                'payment_status' => PaymentStatus::Canceled,
                'canceled_at' => now(),
                'canceled_by' => 'client',
            ]);

            return 'canceled_no_payment';
        }

        $eligibleForRefund = $reservation->start_time->gt(now()->addHours(12));

        if ($eligibleForRefund) {
            $this->issueRefund($reservation);
            $reservation->update([
                'payment_status' => PaymentStatus::Refunded,
                'canceled_at' => now(),
                'canceled_by' => 'client',
                'refunded_at' => now(),
            ]);

            return 'refunded';
        } 
        $reservation->update([
            'payment_status' => PaymentStatus::Canceled,
            'canceled_at' => now(),
            'canceled_by' => 'client',
        ]);

        return 'canceled_late';
    }

    /**
     * Cancelación iniciada por manager/admin. Siempre hay devolución,
     * porque la decisión no fue del cliente.
     */
    public function cancelByManager(Reservation $reservation, string $reason): bool
    {

        if (!in_array($reservation->payment_status, [PaymentStatus::Paid, PaymentStatus::Pending])) {
            throw new ReservationNotCancellableException();
        }

        $hadRealPayment = $reservation->payment_status === PaymentStatus::Paid && $reservation->user_id;

        // Si se pagó la reserva y la realizó un cliente, se realiza el desembolso y se actualiza la reserva
        if ($hadRealPayment && !empty($reservation->user_id)) {
            $this->issueRefund($reservation);

            $reservation->update([
                'payment_status' => PaymentStatus::Refunded,
                'canceled_at' => now(),
                'canceled_by' => 'manager',
                'cancellation_reason' => $reason,
                'refunded_at' => now(),
            ]);
        // Si no se pagó la reserva o la realizó un gestor, no se realiza el desembolso
        } else {
            $reservation->update([
                'payment_status' => PaymentStatus::Canceled,
                'canceled_at' => now(),
                'canceled_by' => 'manager',
                'cancellation_reason' => $reason,
            ]);
        }
        // Si la reserva la hizo un cliente, le enviamos un email informándole de la cancelación
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
                'canceled_by' => 'system',
            ]);
    }

    private function issueRefund(Reservation $reservation): void
    {
        if (!$reservation->payment_id) {
            Log::warning('Intento de reembolso sin payment_id', ['reservation_id' => $reservation->id]);
            
            throw new RefundException('No se puede realizar el reembolso.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $refund = Refund::create([
                'payment_intent' => $reservation->payment_id,
            ]);

            $reservation->stripe_refund_id = $refund->id;
        } catch (\Exception $e) {
            Log::error('Error al crear reembolso en Stripe: ' . $e->getMessage(), [
                'reservation_id' => $reservation->id,
            ]);
            throw new RefundException('No se pudo procesar la devolución. Inténtelo de nuevo o contacta con soporte.');
        }
    }
}