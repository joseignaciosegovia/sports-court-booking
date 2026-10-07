<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Reservation;
use App\Services\Common\StripeWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Event;
use Tests\TestCase;
use Mockery;
use Stripe\Refund;

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

    /** @return StripeWebhookService&\Mockery\MockInterface */
    private function servicio(?\Closure $stripe = null)
    {
        $mock = Mockery::mock(StripeWebhookService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        if ($stripe) {
            $stripe($mock);
        } else {
            $mock->shouldNotReceive('createRefund');
        }

        return $mock;
    }

    public function test_pago_sobre_reserva_pendiente_la_marca_como_pagada(): void
    {
        $reserva = Reservation::factory()->create(['stripe_session_id' => 'cs_test_123']);

        $this->servicio()->handleEvent($this->evento('checkout.session.completed'));

        $this->assertSame(PaymentStatus::Paid, $reserva->fresh()->payment_status);
    }

    public function test_evento_repetido_sobre_reserva_pagada_no_reembolsa(): void
    {
        $reserva = Reservation::factory()->paid()->create(['stripe_session_id' => 'cs_test_123']);

        $this->servicio()->handleEvent($this->evento('checkout.session.completed')); // shouldNotReceive

        $this->assertSame(PaymentStatus::Paid, $reserva->fresh()->payment_status);
    }

    public function test_pago_tardio_sobre_reserva_cancelada_se_reembolsa_y_no_resucita(): void
    {
        $reserva = Reservation::factory()->canceled()->create(['stripe_session_id' => 'cs_test_123']);

        $servicio = $this->servicio(fn ($m) => $m->shouldReceive('createRefund')
            ->once()
            ->andReturn(Refund::constructFrom(['id' => 're_late_1'])));

        $servicio->handleEvent($this->evento('checkout.session.completed'));

        $reserva->refresh();
        $this->assertSame(PaymentStatus::Refunded, $reserva->payment_status);
        $this->assertSame('re_late_1', $reserva->stripe_refund_id);
        $this->assertNotNull($reserva->refunded_at);
    }

    public function test_pago_tardio_sobre_reserva_caducada_se_reembolsa(): void
    {
        $reserva = Reservation::factory()->create([
            'stripe_session_id' => 'cs_test_123',
            'payment_status'    => PaymentStatus::Canceled, // el job de caducidad ya la canceló
            'canceled_at'       => now(),
        ]);

        $servicio = $this->servicio(fn ($m) => $m->shouldReceive('createRefund')
            ->once()
            ->andReturn(Refund::constructFrom(['id' => 're_late_2'])));

        $servicio->handleEvent($this->evento('checkout.session.completed'));

        $this->assertSame(PaymentStatus::Refunded, $reserva->fresh()->payment_status);
    }

    public function test_el_mismo_pago_tardio_dos_veces_solo_reembolsa_una(): void
    {
        $reserva = Reservation::factory()->canceled()->create(['stripe_session_id' => 'cs_test_123']);

        $servicio = $this->servicio(fn ($m) => $m->shouldReceive('createRefund')
            ->once()
            ->andReturn(Refund::constructFrom(['id' => 're_late_1'])));

        $servicio->handleEvent($this->evento('checkout.session.completed'));
        $servicio->handleEvent($this->evento('checkout.session.completed'));

        $this->assertSame(PaymentStatus::Refunded, $reserva->fresh()->payment_status);
    }

    public function test_si_stripe_falla_al_reembolsar_el_error_sube_para_que_stripe_reintente(): void
    {
        $reserva = Reservation::factory()->canceled()->create(['stripe_session_id' => 'cs_test_123']);

        $servicio = $this->servicio(fn ($m) => $m->shouldReceive('createRefund')
            ->andThrow(new \RuntimeException('Stripe caído')));

        $this->expectException(\RuntimeException::class);

        try {
            $servicio->handleEvent($this->evento('checkout.session.completed'));
        } finally {
            $this->assertSame(PaymentStatus::Canceled, $reserva->fresh()->payment_status);
        }
    }
}