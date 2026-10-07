<?php

namespace App\Services\Common;

use App\Enums\PaymentStatus;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Refund;
use Stripe\Stripe;

class StripeWebhookService
{
    public function handleEvent($event): void
    {
        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
            default => null,
        };
    }

    private function handleCheckoutCompleted($session): void
    {
        DB::transaction(function () use ($session) {
            $reservation = Reservation::where('stripe_session_id', $session->id)
                ->lockForUpdate()
                ->first();

            if (! $reservation) {
                return;
            }

            // Evento repetido: ya está pagada
            if ($reservation->payment_status === PaymentStatus::Paid) {
                return;
            }

            // Pago tardío sobre una reserva cancelada o caducada
            if ($reservation->payment_status !== PaymentStatus::Pending) {
                $this->refundLatePayment($reservation, $session->payment_intent);
                return;
            }

            $reservation->update([
                'payment_status' => PaymentStatus::Paid,
                'payment_id'     => $session->payment_intent,
            ]);
        });
    }

    private function refundLatePayment(Reservation $reservation, string $paymentIntent): void
    {
        // Si ya se reembolsó (evento repetido), no volver a hacerlo
        if ($reservation->stripe_refund_id) {
            return;
        }

        Log::warning('Pago tardío sobre reserva no pendiente: se reembolsa', [
            'reservation_id' => $reservation->id,
            'status'         => $reservation->payment_status->value,
        ]);

        // Si Stripe falla, la excepción sube: el webhook devuelve 500 y Stripe reintenta
        $refund = $this->createRefund($paymentIntent, $reservation);

        $reservation->update([
            'payment_status'   => PaymentStatus::Refunded,
            'payment_id'       => $paymentIntent,
            'stripe_refund_id' => $refund->id,
            'refunded_at'      => now(),
        ]);
    }

    protected function createRefund(string $paymentIntent, Reservation $reservation): Refund
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        return Refund::create(
            ['payment_intent' => $paymentIntent],
            ['idempotency_key' => 'late-refund-' . $reservation->id]
        );
    }
}