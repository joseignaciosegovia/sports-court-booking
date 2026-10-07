<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Exceptions\ReservationNotResumableException;
use App\Exceptions\SlotUnavailableException;
use App\Models\Court;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Client\ReservationCheckoutService;
use App\Services\Validation\ReservationRulesValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Checkout\Session;
use Stripe\Exception\InvalidRequestException;
use Tests\TestCase;

class ReservationCheckoutServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'schedules.opening_time' => '08:00',
            'schedules.closing_time' => '22:00',
        ]);
    }

    /**
     * Servicio real, salvo las llamadas a Stripe, que se sustituyen.
     *
     * @return ReservationCheckoutService&\Mockery\MockInterface
     */
    private function servicio(?\Closure $stripe = null)
    {
        $mock = Mockery::mock(
            ReservationCheckoutService::class,
            [app(ReservationRulesValidator::class)]
        )->makePartial()->shouldAllowMockingProtectedMethods();

        if ($stripe) {
            $stripe($mock);
        } else {
            $mock->shouldReceive('createStripeSession')->andReturn(
                Session::constructFrom(['id' => 'cs_test_123', 'url' => 'https://checkout.stripe.test/x'])
            );
        }

        return $mock;
    }

    private function inicio()
    {
        return now()->addDays(3)->setTime(18, 0, 0);
    }

    private function reservaRetomable(array $extra = []): Reservation
    {
        return Reservation::factory()->create([
            'stripe_session_id' => 'cs_test_123',
            'expires_at'        => now()->addMinutes(10),
            ...$extra,
        ]);
    }

    // ───── createReservationWithCheckout ─────

    public function test_crea_la_reserva_pendiente_y_devuelve_la_url_de_stripe(): void
    {
        $user  = User::factory()->client()->create();
        $court = Court::factory()->create();

        $url = $this->servicio()->createReservationWithCheckout($court, $user->id, $this->inicio());

        $this->assertSame('https://checkout.stripe.test/x', $url);

        $reserva = Reservation::firstOrFail();
        $this->assertSame(PaymentStatus::Pending, $reserva->payment_status);
        $this->assertSame('cs_test_123', $reserva->stripe_session_id);
        $this->assertSame($user->id, $reserva->user_id);
        $this->assertSame($court->id, $reserva->court_id);
        $this->assertTrue($reserva->end_time->equalTo($this->inicio()->addHour()));
        $this->assertTrue($reserva->expires_at->isFuture());
    }

    public function test_un_horario_ocupado_lanza_excepcion_y_no_llama_a_stripe(): void
    {
        $user  = User::factory()->client()->create();
        $court = Court::factory()->create();

        Reservation::factory()->paid()->create([
            'court_id'   => $court->id,
            'start_time' => $this->inicio(),
            'end_time'   => $this->inicio()->addHour(),
        ]);

        $servicio = $this->servicio(fn ($m) => $m->shouldNotReceive('createStripeSession'));

        $this->expectException(SlotUnavailableException::class);

        try {
            $servicio->createReservationWithCheckout($court, $user->id, $this->inicio());
        } finally {
            $this->assertDatabaseCount('reservations', 1);
        }
    }

    public function test_fuera_del_horario_de_apertura_lanza_excepcion(): void
    {
        $user  = User::factory()->client()->create();
        $court = Court::factory()->create();

        $servicio = $this->servicio(fn ($m) => $m->shouldNotReceive('createStripeSession'));

        $this->expectException(SlotUnavailableException::class);

        $servicio->createReservationWithCheckout(
            $court, $user->id, now()->addDays(3)->setTime(3, 0, 0)
        );
    }

    public function test_si_stripe_falla_no_queda_una_reserva_huerfana(): void
    {
        $user  = User::factory()->client()->create();
        $court = Court::factory()->create();

        $servicio = $this->servicio(fn ($m) => $m->shouldReceive('createStripeSession')
            ->andThrow(new \RuntimeException('Stripe caído')));

        try {
            $servicio->createReservationWithCheckout($court, $user->id, $this->inicio());
            $this->fail('Debería haber lanzado la excepción de Stripe');
        } catch (\RuntimeException) {
            // esperado
        }

        $this->assertDatabaseCount('reservations', 0);
    }

    // ───── Horarios de apertura y cierre ─────

    public function test_se_puede_reservar_a_la_hora_de_apertura(): void
    {
        $user  = User::factory()->client()->create();
        $court = Court::factory()->create();

        $url = $this->servicio()->createReservationWithCheckout(
            $court, $user->id, now()->addDays(3)->setTime(8, 0, 0)
        );

        $this->assertSame('https://checkout.stripe.test/x', $url);
    }

    public function test_se_puede_reservar_la_ultima_hora_antes_del_cierre(): void
    {
        $user  = User::factory()->client()->create();
        $court = Court::factory()->create();

        // 21:00-22:00: termina justo a la hora de cierre
        $url = $this->servicio()->createReservationWithCheckout(
            $court, $user->id, now()->addDays(3)->setTime(21, 0, 0)
        );

        $this->assertSame('https://checkout.stripe.test/x', $url);
    }

    public function test_no_se_puede_reservar_si_termina_despues_del_cierre(): void
    {
        $user  = User::factory()->client()->create();
        $court = Court::factory()->create();

        $servicio = $this->servicio(fn ($m) => $m->shouldNotReceive('createStripeSession'));

        $this->expectException(SlotUnavailableException::class);

        // 21:30-22:30: empieza dentro de horario pero termina fuera
        $servicio->createReservationWithCheckout(
            $court, $user->id, now()->addDays(3)->setTime(21, 30, 0)
        );
    }

    public function test_no_se_puede_reservar_antes_de_la_apertura(): void
    {
        $user  = User::factory()->client()->create();
        $court = Court::factory()->create();

        $servicio = $this->servicio(fn ($m) => $m->shouldNotReceive('createStripeSession'));

        $this->expectException(SlotUnavailableException::class);

        $servicio->createReservationWithCheckout(
            $court, $user->id, now()->addDays(3)->setTime(7, 0, 0)
        );
    }

    // ───── resumeCheckout ─────

    public function test_no_se_puede_retomar_el_pago_de_una_reserva_pagada(): void
    {
        $this->expectException(ReservationNotResumableException::class);

        $this->servicio()->resumeCheckout(Reservation::factory()->paid()->create());
    }

    public function test_no_se_puede_retomar_el_pago_de_una_reserva_caducada(): void
    {
        $this->expectException(ReservationNotResumableException::class);

        $this->servicio()->resumeCheckout(Reservation::factory()->expired()->create());
    }

    public function test_retomar_el_pago_devuelve_la_url_si_la_sesion_sigue_abierta(): void
    {
        $servicio = $this->servicio(fn ($m) => $m->shouldReceive('stripeRetrieveSession')
            ->once()
            ->with('cs_test_123')
            ->andReturn(Session::constructFrom([
                'id' => 'cs_test_123', 'status' => 'open', 'url' => 'https://checkout.stripe.test/retomar',
            ])));

        $this->assertSame(
            'https://checkout.stripe.test/retomar',
            $servicio->resumeCheckout($this->reservaRetomable())
        );
    }

    public function test_no_se_retoma_si_la_sesion_ya_no_esta_abierta(): void
    {
        $servicio = $this->servicio(fn ($m) => $m->shouldReceive('stripeRetrieveSession')
            ->andReturn(Session::constructFrom([
                'id' => 'cs_test_123', 'status' => 'complete', 'url' => null,
            ])));

        $this->expectException(ReservationNotResumableException::class);

        $servicio->resumeCheckout($this->reservaRetomable());
    }

    public function test_si_stripe_falla_al_recuperar_la_sesion_no_se_puede_retomar(): void
    {
        $servicio = $this->servicio(fn ($m) => $m->shouldReceive('stripeRetrieveSession')
            ->andThrow(new InvalidRequestException('No such session')));

        $this->expectException(ReservationNotResumableException::class);

        $servicio->resumeCheckout($this->reservaRetomable());
    }

    public function test_una_reserva_sin_sesion_de_stripe_no_se_puede_retomar(): void
    {
        $servicio = $this->servicio(fn ($m) => $m->shouldNotReceive('stripeRetrieveSession'));

        $this->expectException(ReservationNotResumableException::class);

        $servicio->resumeCheckout($this->reservaRetomable(['stripe_session_id' => null]));
    }
}