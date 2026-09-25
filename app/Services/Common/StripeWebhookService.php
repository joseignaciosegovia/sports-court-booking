<?php

namespace App\Services\Common;

use App\Models\Reservation;
use App\Enums\PaymentStatus;

class StripeWebhookService
{
    public function handleEvent($event): void
    {
        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
            // futuros casos:
            // 'payment_intent.payment_failed' => $this->handlePaymentFailed($event->data->object),
            // 'charge.refunded' => $this->handleRefund($event->data->object),
            default => null, // evento que no nos interesa, se ignora
        };
    }

    private function handleCheckoutCompleted($session): void
    {
        \Log::info('Stripe checkout.session.completed', [
        'session_id' => $session->id,
        'payment_intent' => $session->payment_intent,
    ]);
        $reservation = Reservation::where('stripe_session_id', $session->id)->first();

        \Log::info('Reserva encontrada', [
        'reservation_id' => $reservation?->id,
        'stripe_session_id' => $reservation?->stripe_session_id,
    ]);

        if ($reservation) {
            $reservation->update([
                'payment_status' => PaymentStatus::Paid,
                'payment_id' => $session->payment_intent,
            ]);
        }
    }
}