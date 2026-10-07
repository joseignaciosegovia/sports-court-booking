<?php

namespace Tests\Feature;

use App\Enums\CanceledBy;
use App\Enums\PaymentStatus;
use App\Exceptions\RefundException;
use App\Exceptions\ReservationNotCancellableException;
use App\Mail\ReservationCanceledByManagerMail;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Common\ReservationCancellationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Stripe\Refund;
use Tests\TestCase;

class ReservationCancellationServiceTest extends TestCase
{
    use RefreshDatabase;

    /** Servicio real, salvo la llamada a Stripe, que se sustituye. 
     * 
     *  @return ReservationCancellationService&\Mockery\MockInterface
    */
    private function servicio(?\Closure $stripe = null)
    {
        $mock = Mockery::mock(ReservationCancellationService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        if ($stripe) {
            $stripe($mock);
        } else {
            $mock->shouldReceive('createRefund')
                ->andReturn(Refund::constructFrom(['id' => 're_test_1']));
        }

        return $mock;
    }

    /** Para los casos en los que NO debe llamarse a Stripe. */
    private function sinStripe(): \Closure
    {
        return fn ($m) => $m->shouldNotReceive('createRefund');
    }

    /** Reserva pagada que empieza dentro de $horasHastaInicio horas. */
    private function pagada(int $horasHastaInicio, array $extra = []): Reservation
    {
        $inicio = now()->addHours($horasHastaInicio);

        return Reservation::factory()->paid()->create([
            'start_time' => $inicio,
            'end_time'   => $inicio->copy()->addHour(),
            ...$extra,
        ]);
    }

    // ───── Cancelación por el cliente ─────

    public function test_cancelar_una_reserva_pendiente_no_llama_a_stripe(): void
    {
        $reserva = Reservation::factory()->create();

        $resultado = $this->servicio($this->sinStripe())->cancelByClient($reserva);

        $this->assertSame('canceled_no_payment', $resultado);

        $reserva->refresh();
        $this->assertSame(PaymentStatus::Canceled, $reserva->payment_status);
        $this->assertSame(CanceledBy::Client, $reserva->canceled_by);
        $this->assertNotNull($reserva->canceled_at);
    }

    public function test_cancelar_con_mas_de_12h_reembolsa(): void
    {
        $reserva = $this->pagada(13);

        $resultado = $this->servicio()->cancelByClient($reserva);

        $this->assertSame('refunded', $resultado);

        $reserva->refresh();
        $this->assertSame(PaymentStatus::Refunded, $reserva->payment_status);
        $this->assertSame(CanceledBy::Client, $reserva->canceled_by);
        $this->assertSame('re_test_1', $reserva->stripe_refund_id);
        $this->assertNotNull($reserva->refunded_at);
        $this->assertNotNull($reserva->canceled_at);
    }

    public function test_cancelar_con_menos_de_12h_no_reembolsa(): void
    {
        $reserva = $this->pagada(11);

        $resultado = $this->servicio($this->sinStripe())->cancelByClient($reserva);

        $this->assertSame('canceled_late', $resultado);

        $reserva->refresh();
        $this->assertSame(PaymentStatus::Canceled, $reserva->payment_status);
        $this->assertSame(CanceledBy::Client, $reserva->canceled_by);
        $this->assertNull($reserva->stripe_refund_id);
        $this->assertNull($reserva->refunded_at);
    }

    public function test_si_stripe_falla_la_reserva_sigue_pagada(): void
    {
        $reserva = $this->pagada(48);

        $servicio = $this->servicio(fn ($m) => $m->shouldReceive('createRefund')
            ->andThrow(new \RuntimeException('Stripe caído')));

        try {
            $servicio->cancelByClient($reserva);
            $this->fail('Debería lanzar RefundException');
        } catch (RefundException) {
            // esperado
        }

        $reserva->refresh();
        $this->assertSame(PaymentStatus::Paid, $reserva->payment_status);
        $this->assertNull($reserva->canceled_at);
        $this->assertNull($reserva->canceled_by);
        $this->assertNull($reserva->stripe_refund_id);
    }

    public function test_sin_payment_id_no_se_puede_reembolsar(): void
    {
        $reserva = $this->pagada(48, ['payment_id' => null]);

        $this->expectException(RefundException::class);

        $this->servicio($this->sinStripe())->cancelByClient($reserva);
    }

    public function test_una_reserva_ya_cancelada_o_reembolsada_no_se_puede_cancelar_otra_vez(): void
    {
        foreach ([PaymentStatus::Refunded, PaymentStatus::Canceled] as $estado) {
            $reserva = Reservation::factory()->create(['payment_status' => $estado]);

            try {
                $this->servicio($this->sinStripe())->cancelByClient($reserva);
                $this->fail("Debería rechazar una reserva en estado {$estado->value}");
            } catch (ReservationNotCancellableException) {
                // esperado
            }

            $this->assertSame($estado, $reserva->fresh()->payment_status);
        }
    }

    public function test_cancelar_dos_veces_solo_reembolsa_una(): void
    {
        $reserva = $this->pagada(48);

        $servicio = $this->servicio(fn ($m) => $m->shouldReceive('createRefund')
            ->once()
            ->andReturn(Refund::constructFrom(['id' => 're_test_1'])));

        $servicio->cancelByClient($reserva);

        try {
            // Mismo modelo en memoria, que sigue figurando como Paid:
            // el servicio debe releer la fila de la base de datos.
            $servicio->cancelByClient($reserva);
            $this->fail('La segunda cancelación debería rechazarse');
        } catch (ReservationNotCancellableException) {
            // esperado
        }

        $this->assertSame(PaymentStatus::Refunded, $reserva->fresh()->payment_status);
    }

    // ───── Cancelación por el manager ─────

    public function test_el_manager_cancela_una_reserva_pagada_y_se_reembolsa_siempre(): void
    {
        Mail::fake();
        $cliente = User::factory()->client()->create();
        $reserva = $this->pagada(1, ['user_id' => $cliente->id]); // incluso a 1 hora

        $huboPago = $this->servicio()->cancelByManager($reserva, 'Pista en mantenimiento');

        $this->assertTrue($huboPago);

        $reserva->refresh();
        $this->assertSame(PaymentStatus::Refunded, $reserva->payment_status);
        $this->assertSame(CanceledBy::Manager, $reserva->canceled_by);
        $this->assertSame('Pista en mantenimiento', $reserva->cancellation_reason);
        $this->assertSame('re_test_1', $reserva->stripe_refund_id);
        $this->assertNotNull($reserva->refunded_at);

        Mail::assertQueued(ReservationCanceledByManagerMail::class);
    }

    public function test_el_manager_cancela_una_reserva_pendiente_sin_reembolso_pero_avisa_al_cliente(): void
    {
        Mail::fake();
        $reserva = Reservation::factory()->create(); // pending, con cliente

        $huboPago = $this->servicio($this->sinStripe())->cancelByManager($reserva, 'Motivo');

        $this->assertFalse($huboPago);

        $reserva->refresh();
        $this->assertSame(PaymentStatus::Canceled, $reserva->payment_status);
        $this->assertSame(CanceledBy::Manager, $reserva->canceled_by);
        $this->assertNull($reserva->refunded_at);

        Mail::assertQueued(ReservationCanceledByManagerMail::class);
    }

    public function test_el_manager_cancela_una_reserva_sin_cliente_sin_reembolso_ni_email(): void
    {
        Mail::fake();
        $reserva = $this->pagada(48, ['user_id' => null]);

        $huboPago = $this->servicio($this->sinStripe())->cancelByManager($reserva, 'Motivo');

        $this->assertFalse($huboPago);

        $reserva->refresh();
        $this->assertSame(PaymentStatus::Canceled, $reserva->payment_status);
        $this->assertSame(CanceledBy::Manager, $reserva->canceled_by);

        Mail::assertNothingQueued();
    }

    public function test_si_stripe_falla_el_manager_no_cancela_la_reserva(): void
    {
        Mail::fake();
        $cliente = User::factory()->client()->create();
        $reserva = $this->pagada(48, ['user_id' => $cliente->id]);

        $servicio = $this->servicio(fn ($m) => $m->shouldReceive('createRefund')
            ->andThrow(new \RuntimeException('Stripe caído')));

        try {
            $servicio->cancelByManager($reserva, 'Motivo');
            $this->fail('Debería lanzar RefundException');
        } catch (RefundException) {
            // esperado
        }

        $this->assertSame(PaymentStatus::Paid, $reserva->fresh()->payment_status);
        Mail::assertNothingQueued(); // no se avisa de una cancelación que no ocurrió
    }

    public function test_el_manager_no_puede_cancelar_una_reserva_ya_cancelada(): void
    {
        $reserva = Reservation::factory()->canceled()->create();

        $this->expectException(ReservationNotCancellableException::class);

        $this->servicio($this->sinStripe())->cancelByManager($reserva, 'Motivo');
    }

    // ───── Caducadas ─────

    public function test_se_cancelan_solo_las_pendientes_caducadas(): void
    {
        $caducada = Reservation::factory()->expired()->create();
        $vigente  = Reservation::factory()->create(); // expires_at futuro
        $pagada   = Reservation::factory()->paid()->create(['expires_at' => now()->subHour()]);

        $total = (new ReservationCancellationService)->cancelExpiredReservations();

        $this->assertSame(1, $total);

        $this->assertSame(PaymentStatus::Canceled, $caducada->fresh()->payment_status);
        $this->assertSame(CanceledBy::System, $caducada->fresh()->canceled_by);
        $this->assertSame(PaymentStatus::Pending, $vigente->fresh()->payment_status);
        $this->assertSame(PaymentStatus::Paid, $pagada->fresh()->payment_status);
    }
}