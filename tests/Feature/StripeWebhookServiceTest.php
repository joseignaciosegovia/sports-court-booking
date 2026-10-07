<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Reservation;
use App\Services\Common\StripeWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Event;
use Tests\TestCase;

class StripeWebhookServiceTest extends TestCase
{
    use RefreshDatabase;

    private function evento(string $tipo, string $sessionId = 'cs_test_123'): Event
    {
        return Event::constructFrom([
            'id'   => 'evt_test_1',
            'type' => $tipo,
            'data' => ['object' => [
                'id'             => $sessionId,
                'payment_intent' => 'pi_test_123',
            ]],
        ]);
    }

    public function test_checkout_completado_marca_la_reserva_como_pagada(): void
    {
        $reserva = Reservation::factory()->create(['stripe_session_id' => 'cs_test_123']);

        (new StripeWebhookService)->handleEvent($this->evento('checkout.session.completed'));

        $reserva->refresh();
        $this->assertSame(PaymentStatus::Paid, $reserva->payment_status);
        $this->assertSame('pi_test_123', $reserva->payment_id);
    }

    public function test_una_sesion_desconocida_no_hace_nada_ni_falla(): void
    {
        $reserva = Reservation::factory()->create(['stripe_session_id' => 'cs_otra']);

        (new StripeWebhookService)->handleEvent($this->evento('checkout.session.completed'));

        $this->assertSame(PaymentStatus::Pending, $reserva->fresh()->payment_status);
    }

    public function test_un_evento_de_otro_tipo_se_ignora(): void
    {
        $reserva = Reservation::factory()->create(['stripe_session_id' => 'cs_test_123']);

        (new StripeWebhookService)->handleEvent($this->evento('invoice.paid'));

        $this->assertSame(PaymentStatus::Pending, $reserva->fresh()->payment_status);
    }

    public function test_el_mismo_evento_dos_veces_es_idempotente(): void
    {
        $reserva = Reservation::factory()->create(['stripe_session_id' => 'cs_test_123']);
        $servicio = new StripeWebhookService;

        $servicio->handleEvent($this->evento('checkout.session.completed'));
        $servicio->handleEvent($this->evento('checkout.session.completed'));

        $this->assertSame(PaymentStatus::Paid, $reserva->fresh()->payment_status);
        $this->assertDatabaseCount('reservations', 1);
    }
}