<?php

namespace App\Services\Client;

use App\Exceptions\SlotUnavailableException;
use App\Services\Validation\ReservationRulesValidator;
use Illuminate\Support\Facades\Log;
use App\Models\Reservation;
use App\Models\Court;
use Carbon\Carbon;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class ReservationCheckoutService
{
    public function __construct(
        private ReservationRulesValidator $rules
    ) {}

    /**
     * Crea una reserva en estado pendiente y su sesión de pago en Stripe.
     * Devuelve la URL de checkout a la que redirigir al cliente.
     *
     * @throws SlotUnavailableException si la franja ya está ocupada
     */
    public function createReservationWithCheckout(Court $court, int $userId, Carbon $startTime): string
    {
        $endTime = $startTime->copy()->addHour();

        if ($this->rules->checkWithinOpeningHours($startTime, $endTime) !== null) {
            throw new SlotUnavailableException();
        }

        if ($this->rules->checkOverlap($court->id, $startTime, $endTime) !== null) {
            throw new SlotUnavailableException();
        }

        $reservation = $this->createPendingReservation($court, $userId, $startTime, $endTime);

        if ($this->rules->checkClientRestrictions($reservation, $startTime, $endTime) !== null) {
            $reservation->delete();
            throw new SlotUnavailableException();
        }

        $session = $this->createStripeSession($court, $startTime, $reservation);

        $reservation->update(['stripe_session_id' => $session->id]);

        return $session->url;
    }

    private function createPendingReservation(Court $court, int $userId, Carbon $startTime, Carbon $endTime): Reservation
    {
        return Reservation::create([
            'court_id' => $court->id,
            'user_id' => $userId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'information' => 'Reserva creada por un cliente',
            'payment_status' => 'pending',
            'expires_at' => now()->addMinutes(15),
        ]);
    }

    private function createStripeSession(Court $court, Carbon $startTime, Reservation $reservation): Session
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            return Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => "Reserva de {$court->name}",
                            'description' => $startTime->translatedFormat('d \d\e F \d\e Y, H:i'),
                        ],
                        'unit_amount' => (int) round($court->reservation_price * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'expires_at' => now()->addMinutes(30)->timestamp,
                'success_url' => route('client.reservations.payment.success', $reservation) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('client.reservations.payment.cancel', $reservation),
            ]);
        } catch (\Exception $e) {
            Log::error('Error creando sesión de Stripe: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Recupera la URL de pago de una reserva pendiente para que el cliente
     * pueda retomar el checkout si lo abandonó a mitad.
     *
     * @throws \App\Exceptions\ReservationNotResumableException
     */
    public function resumeCheckout(Reservation $reservation): string
    {
        if ($reservation->payment_status !== 'pending'
            || !$reservation->expires_at
            || $reservation->expires_at->isPast()) {
            throw new \App\Exceptions\ReservationNotResumableException();
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $session = Session::retrieve($reservation->stripe_session_id);
        } catch (ApiErrorException $e) {
            Log::error('Error recuperando sesión de Stripe: ' . $e->getMessage(), [
                'reservation_id' => $reservation->id,
            ]);
            throw new \App\Exceptions\ReservationNotResumableException();
        }

        // Por si el webhook ya la marcó como pagada justo en este instante (carrera improbable)
        if ($session->status !== 'open') {
            throw new \App\Exceptions\ReservationNotResumableException();
        }

        return $session->url;
    }
}