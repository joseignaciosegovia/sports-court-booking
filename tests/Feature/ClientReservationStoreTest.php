<?php

namespace Tests\Feature;

use App\Models\Court;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Exceptions\SlotUnavailableException;
use App\Services\Client\ReservationCheckoutService;
use App\Services\Validation\ReservationRulesValidator;
use Mockery;
use Stripe\Checkout\Session;

class ClientReservationStoreTest extends TestCase
{
    use RefreshDatabase;

    private function datosValidos(Court $court): array
    {
        $inicio = now()->addDays(3)->setTime(18, 0, 0);

        return [
            'court_id'   => $court->id,
            'start_time' => $inicio->format('Y-m-d H:i:s'),
            'end_time'   => $inicio->copy()->addHour()->format('Y-m-d H:i:s'),
        ];
    }

    public function test_los_campos_obligatorios_se_validan(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
             ->post(route('client.reservations.store'), [])
             ->assertSessionHasErrors(['court_id', 'start_time']); // ajusta
    }

    public function test_no_se_puede_reservar_una_pista_inexistente(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
             ->post(route('client.reservations.store'), [
                 'court_id' => 9999,
                 'start_time' => now()->addDay()->toDateTimeString(),
                 'end_time' => now()->addDay()->addHour()->toDateTimeString(),
             ])
             ->assertSessionHasErrors('court_id');
    }

    public function test_no_se_puede_reservar_en_el_pasado(): void
    {
        $client = User::factory()->client()->create();
        $court = Court::factory()->create();

        $this->actingAs($client)
             ->post(route('client.reservations.store'), [
                 'court_id' => $court->id,
                 'start_time' => now()->subDay()->toDateTimeString(),
                 'end_time' => now()->subDay()->addHour()->toDateTimeString(),
             ])
             ->assertSessionHasErrors();

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_no_se_puede_reservar_un_horario_ocupado(): void
    {
        $client = User::factory()->client()->create();
        $court = Court::factory()->create();
        $datos = $this->datosValidos($court);

        Reservation::factory()->create([
            'court_id'   => $court->id,
            'start_time' => $datos['start_time'],
            'end_time'   => $datos['end_time'],
        ]);

        $this->actingAs($client)->post(route('client.reservations.store'), $datos);

        $this->assertDatabaseCount('reservations', 1); // no se creó otra
    }

    public function test_una_reserva_cancelada_libera_el_horario(): void
    {
        $client = User::factory()->client()->create();
        $court = Court::factory()->create();
        $datos = $this->datosValidos($court);

        Reservation::factory()->canceled()->create([
            'court_id'   => $court->id,
            'start_time' => $datos['start_time'],
            'end_time'   => $datos['end_time'],
        ]);

        $this->fingirStripe();

        $this->actingAs($client)
            ->post(route('client.reservations.store'), $datos)
            ->assertRedirect('https://checkout.stripe.test/x');

        $this->assertDatabaseCount('reservations', 2);
    }

    public function test_no_se_puede_reservar_un_horario_con_reserva_pagada(): void
    {
        $client = User::factory()->client()->create();
        $court = Court::factory()->create();
        $datos = $this->datosValidos($court);

        Reservation::factory()->paid()->create([
            'court_id'   => $court->id,
            'start_time' => $datos['start_time'],
            'end_time'   => $datos['end_time'],
        ]);

        $this->actingAs($client)->post(route('client.reservations.store'), $datos);

        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_no_se_puede_reservar_un_horario_con_pago_pendiente_vigente(): void
    {
        $client = User::factory()->client()->create();
        $court = Court::factory()->create();
        $datos = $this->datosValidos($court);

        Reservation::factory()->create([
            'court_id'   => $court->id,
            'start_time' => $datos['start_time'],
            'end_time'   => $datos['end_time'],
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->actingAs($client)->post(route('client.reservations.store'), $datos);

        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_una_reserva_pendiente_caducada_libera_el_horario(): void
    {
        $client = User::factory()->client()->create();
        $court = Court::factory()->create();
        $datos = $this->datosValidos($court);

        Reservation::factory()->expired()->create([
            'court_id'   => $court->id,
            'start_time' => $datos['start_time'],
            'end_time'   => $datos['end_time'],
        ]);

        $this->fingirStripe();

        $this->actingAs($client)
            ->post(route('client.reservations.store'), $datos)
            ->assertRedirect('https://checkout.stripe.test/x');

        $this->assertDatabaseCount('reservations', 2);
    }

    public function test_no_se_puede_reservar_con_solapamiento_parcial(): void
    {
        $client = User::factory()->client()->create();
        $court = Court::factory()->create();
        $inicio = now()->addDays(3)->setTime(18, 0, 0);

        Reservation::factory()->paid()->create([
            'court_id'   => $court->id,
            'start_time' => $inicio,
            'end_time'   => $inicio->copy()->addHour(),
        ]);

        $this->actingAs($client)->post(route('client.reservations.store'), [
            'court_id'   => $court->id,
            'start_time' => $inicio->copy()->addMinutes(30)->format('Y-m-d H:i:s'),
            'end_time'   => $inicio->copy()->addMinutes(90)->format('Y-m-d H:i:s'),
        ]);

        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_una_reserva_valida_redirige_a_stripe_checkout(): void
    {
        $client = User::factory()->client()->create();
        $court  = Court::factory()->create();
        $inicio = now()->addDays(3)->setTime(18, 0, 0);

        $this->mock(ReservationCheckoutService::class, function ($mock) use ($court, $client, $inicio) {
            $mock->shouldReceive('createReservationWithCheckout')
                ->once()
                ->withArgs(fn ($c, $userId, $start) =>
                    $c->is($court)
                    && $userId === $client->id
                    && $start->equalTo($inicio)
                )
                ->andReturn('https://checkout.stripe.test/sesion');
        });

        $this->actingAs($client)
            ->post(route('client.reservations.store'), [
                'court_id'   => $court->id,
                'start_time' => $inicio->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect('https://checkout.stripe.test/sesion');
    }

    public function test_si_el_horario_ya_no_esta_disponible_se_muestra_el_error(): void
    {
        $client = User::factory()->client()->create();
        $court  = Court::factory()->create();

        $this->mock(ReservationCheckoutService::class, function ($mock) {
            $mock->shouldReceive('createReservationWithCheckout')
                ->once()
                ->andThrow(new SlotUnavailableException());
        });

        $this->actingAs($client)
            ->from(route('client.reservations.create'))
            ->post(route('client.reservations.store'), [
                'court_id'   => $court->id,
                'start_time' => now()->addDays(3)->setTime(18, 0)->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('client.reservations.create'))
            ->assertSessionHasErrors('start_time');
    }

    public function test_un_invitado_no_puede_reservar(): void
    {
        $court = Court::factory()->create();

        $this->post(route('client.reservations.store'), [
            'court_id'   => $court->id,
            'start_time' => now()->addDays(3)->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('login'));
    }

    /** Servicio real, salvo la llamada a Stripe, que devuelve una sesión falsa. */
    private function fingirStripe(): void
    {
        $servicio = Mockery::mock(
            ReservationCheckoutService::class,
            [app(ReservationRulesValidator::class)]
        )->makePartial()->shouldAllowMockingProtectedMethods();

        $servicio->shouldReceive('createStripeSession')->andReturn(
            Session::constructFrom([
                'id'  => 'cs_test_123',
                'url' => 'https://checkout.stripe.test/x',
            ])
        );

        $this->app->instance(ReservationCheckoutService::class, $servicio);
    }
}