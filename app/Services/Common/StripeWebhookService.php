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
        error_log('========== STRIPE CHECKOUT COMPLETED ==========');
    error_log('STRIPE SESSION ID: ' . $session->id);
        $reservation = Reservation::where('stripe_session_id', $session->id)->first();

        if (!$reservation) {
        error_log('RESERVA NO ENCONTRADA');
        return;
    }
    error_log('RESERVA ENCONTRADA: ' . $reservation->id);

        if ($reservation) {
            $reservation->update([
                'payment_status' => PaymentStatus::Paid,
                'payment_id' => $session->payment_intent,
            ]);
        }
        error_log('RESERVA ACTUALIZADA A PAID');
    }
}