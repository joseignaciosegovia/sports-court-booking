<?php

namespace App\Services\Common;

use App\Models\Reservation;

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
        $reservation = Reservation::where('stripe_session_id', $session->id)->first();

        if ($reservation) {
            $reservation->update([
                'payment_status' => 'paid',
                'payment_id' => $session->payment_intent,
            ]);
        }
    }
}