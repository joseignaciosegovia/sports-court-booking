<?php

namespace Tests\Feature;

use App\Enums\CanceledBy;
use App\Enums\PaymentStatus;
use App\Mail\ReservationCanceledByManagerMail;
use App\Models\Court;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Common\ReservationCancellationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Stripe\Refund;
use Tests\TestCase;
use App\Mail\ReservationRescheduledMail;

class ManagerReservationTest extends TestCase
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

    private function manager(): User
    {
        return User::factory()->manager()->create();
    }

    private function inicio(int $hora = 18, int $minuto = 0)
    {
        return now()->addDays(3)->setTime($hora, $minuto, 0);
    }

    private function formato($fecha): string
    {
        return $fecha->format('Y-m-d\TH:i:s');
    }

    /** Reserva interna (sin cliente) en un hueco concreto. */
    private function interna(Court $court, $inicio, array $extra = []): Reservation
    {
        return Reservation::factory()->withoutUser()->create([
            'court_id'   => $court->id,
            'start_time' => $inicio,
            'end_time'   => $inicio->copy()->addHour(),
            ...$extra,
        ]);
    }

    private function fingirReembolso(?\Throwable $error = null): void
    {
        $mock = Mockery::mock(ReservationCancellationService::class)
            ->makePartial()->shouldAllowMockingProtectedMethods();

        if ($error) {
            $mock->shouldReceive('createRefund')->andThrow($error);
        } else {
            $mock->shouldReceive('createRefund')
                ->andReturn(Refund::constructFrom(['id' => 're_test_1']));
        }

        $mock->shouldReceive('stripeExpireSession')->byDefault();

        $this->app->instance(ReservationCancellationService::class, $mock);
    }

    // ───── Cancelar ─────

    public function test_cancelar_exige_un_motivo(): void
    {
        $reserva = Reservation::factory()->create();

        $this->actingAs($this->manager())
            ->patch(route('manager.reservations.cancel', $reserva), [])
            ->assertSessionHasErrors('reason');

        $this->assertSame(PaymentStatus::Pending, $reserva->fresh()->payment_status);
    }

    public function test_el_manager_cancela_una_reserva_pagada_la_reembolsa_y_avisa(): void
    {
        Mail::fake();
        $reserva = Reservation::factory()->paid()->create();
        $this->fingirReembolso();

        $this->actingAs($this->manager())
            ->patch(route('manager.reservations.cancel', $reserva), ['reason' => 'Pista en obras'])
            ->assertRedirect(route('manager.reservations.index'))
            ->assertSessionHas('success', 'Reserva cancelada y reembolsada correctamente.');

        $reserva->refresh();
        $this->assertSame(PaymentStatus::Refunded, $reserva->payment_status);
        $this->assertSame(CanceledBy::Manager, $reserva->canceled_by);
        $this->assertSame('Pista en obras', $reserva->cancellation_reason);
        $this->assertSame('re_test_1', $reserva->stripe_refund_id);

        Mail::assertQueued(ReservationCanceledByManagerMail::class);
    }

    public function test_el_manager_cancela_una_reserva_pendiente_sin_reembolso(): void
    {
        Mail::fake();
        $reserva = Reservation::factory()->create(['stripe_session_id' => 'cs_test_1']);

        $this->fingirReembolso();

        $this->actingAs($this->manager())
            ->patch(route('manager.reservations.cancel', $reserva), ['reason' => 'Motivo'])
            ->assertRedirect(route('manager.reservations.index'))
            ->assertSessionHas('success', 'Reserva cancelada correctamente. No había ningún pago que reembolsar.');

        $this->assertSame(PaymentStatus::Canceled, $reserva->fresh()->payment_status);
        Mail::assertQueued(ReservationCanceledByManagerMail::class);
    }

    public function test_cancelar_una_reserva_interna_no_reembolsa_ni_envia_email(): void
    {
        Mail::fake();
        $reserva = Reservation::factory()->withoutUser()->paid()->create();

        $mock = Mockery::mock(ReservationCancellationService::class)
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $mock->shouldNotReceive('createRefund');
        $this->app->instance(ReservationCancellationService::class, $mock);

        $this->actingAs($this->manager())
            ->patch(route('manager.reservations.cancel', $reserva), ['reason' => 'Motivo'])
            ->assertRedirect(route('manager.reservations.index'));

        $this->assertSame(PaymentStatus::Canceled, $reserva->fresh()->payment_status);
        Mail::assertNothingQueued();
    }

    public function test_cancelar_una_reserva_ya_cancelada_muestra_error_y_no_da_500(): void
    {
        $reserva = Reservation::factory()->canceled()->create();

        $this->actingAs($this->manager())
            ->from(route('manager.reservations.index'))
            ->patch(route('manager.reservations.cancel', $reserva), ['reason' => 'Motivo'])
            ->assertRedirect(route('manager.reservations.index'))
            ->assertSessionHasErrors('reservation');
    }

    public function test_si_stripe_falla_al_reembolsar_el_manager_ve_el_error_y_la_reserva_sigue_pagada(): void
    {
        Mail::fake();
        $reserva = Reservation::factory()->paid()->create();
        $this->fingirReembolso(new \RuntimeException('Stripe caído'));

        $this->actingAs($this->manager())
            ->from(route('manager.reservations.index'))
            ->patch(route('manager.reservations.cancel', $reserva), ['reason' => 'Motivo'])
            ->assertRedirect(route('manager.reservations.index'))
            ->assertSessionHasErrors('reservation');

        $this->assertSame(PaymentStatus::Paid, $reserva->fresh()->payment_status);
        Mail::assertNothingQueued();
    }

    public function test_un_admin_tambien_puede_cancelar(): void
    {
        $reserva = Reservation::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('manager.reservations.cancel', $reserva), ['reason' => 'Motivo'])
            ->assertRedirect(route('manager.reservations.index'));

        $this->assertSame(PaymentStatus::Canceled, $reserva->fresh()->payment_status);
    }

    public function test_un_client_no_puede_cancelar_desde_la_zona_del_manager(): void
    {
        $client  = User::factory()->client()->create();
        $reserva = Reservation::factory()->create(['user_id' => $client->id]);

        $this->actingAs($client)
            ->patch(route('manager.reservations.cancel', $reserva), ['reason' => 'Motivo'])
            ->assertForbidden();

        $this->assertSame(PaymentStatus::Pending, $reserva->fresh()->payment_status);
    }

    public function test_un_invitado_va_al_login_de_intranet_al_cancelar(): void
    {
        $reserva = Reservation::factory()->create();

        $this->patch(route('manager.reservations.cancel', $reserva), ['reason' => 'Motivo'])
            ->assertRedirect(route('intranet.login'));
    }

    // ───── Crear (formulario) ─────

    private function datosStore(Court $court, array $extra = []): array
    {
        return [
            'court_id'        => $court->id,
            'date'            => $this->inicio()->format('Y-m-d'),
            'start_time_only' => '18:00',
            'end_time_only'   => '19:00',
            'information'     => 'Torneo del club',
            ...$extra,
        ];
    }

    public function test_crear_exige_los_campos_obligatorios(): void
    {
        $this->actingAs($this->manager())
            ->post(route('manager.reservations.store'), [])
            ->assertSessionHasErrors(['court_id', 'date', 'start_time_only', 'end_time_only', 'information']);

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_la_hora_de_fin_debe_ser_posterior_a_la_de_inicio(): void
    {
        $court = Court::factory()->create();

        $this->actingAs($this->manager())
            ->post(route('manager.reservations.store'), $this->datosStore($court, [
                'start_time_only' => '19:00',
                'end_time_only'   => '18:00',
            ]))
            ->assertSessionHasErrors('end_time_only');
    }

    public function test_el_manager_crea_una_reserva_interna(): void
    {
        $court = Court::factory()->create();

        $this->actingAs($this->manager())
            ->post(route('manager.reservations.store'), $this->datosStore($court))
            ->assertRedirect(route('manager.reservations.index', ['court_id' => $court->id]))
            ->assertSessionHas('success', 'Reserva creada correctamente.');

        $this->assertDatabaseHas('reservations', [
            'court_id'    => $court->id,
            'information' => 'Torneo del club',
            'user_id'     => null,
            'payment_status' => 'paid',   // se fuerza a paid para que bloquee el horario
        ]);
    }

    public function test_no_se_puede_crear_una_reserva_que_se_solapa(): void
    {
        $court = Court::factory()->create();
        $this->interna($court, $this->inicio());

        $this->actingAs($this->manager())
            ->post(route('manager.reservations.store'), $this->datosStore($court, [
                'start_time_only' => '18:30',
                'end_time_only'   => '19:30',
            ]))
            ->assertSessionHasErrors('start_time_only');

        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_no_se_puede_crear_una_reserva_fuera_de_horario(): void
    {
        $court = Court::factory()->create();

        $this->actingAs($this->manager())
            ->post(route('manager.reservations.store'), $this->datosStore($court, [
                'start_time_only' => '21:30',
                'end_time_only'   => '22:30',
            ]))
            ->assertSessionHasErrors('start_time_only');

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_se_puede_crear_la_ultima_franja_justo_hasta_el_cierre(): void
    {
        $court = Court::factory()->create();

        $this->actingAs($this->manager())
            ->post(route('manager.reservations.store'), $this->datosStore($court, [
                'start_time_only' => '21:00',
                'end_time_only'   => '22:00',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('reservations', 1);
    }

    // ───── Creación rápida (calendario) ─────

    public function test_la_creacion_rapida_crea_la_reserva_y_devuelve_json(): void
    {
        $court  = Court::factory()->create();
        $inicio = $this->inicio();

        $this->actingAs($this->manager())
            ->postJson(route('manager.reservations.quick.store', $court), [
                'start'       => $this->formato($inicio),
                'end'         => $this->formato($inicio->copy()->addHour()),
                'information' => 'Clase de pádel',
            ])
            ->assertOk()
            ->assertJsonStructure(['message', 'id']);

        $this->assertDatabaseHas('reservations', ['court_id' => $court->id, 'information' => 'Clase de pádel']);
    }

    public function test_la_creacion_rapida_rechaza_una_fecha_pasada(): void
    {
        $court  = Court::factory()->create();
        $inicio = now()->subDay()->setTime(18, 0);

        $this->actingAs($this->manager())
            ->postJson(route('manager.reservations.quick.store', $court), [
                'start' => $this->formato($inicio),
                'end'   => $this->formato($inicio->copy()->addHour()),
            ])
            ->assertStatus(422)
            ->assertJson(['message' => 'No se puede crear una reserva en una fecha pasada.']);

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_la_creacion_rapida_rechaza_un_solapamiento(): void
    {
        $court  = Court::factory()->create();
        $inicio = $this->inicio();
        $this->interna($court, $inicio);

        $this->actingAs($this->manager())
            ->postJson(route('manager.reservations.quick.store', $court), [
                'start' => $this->formato($inicio->copy()->addMinutes(30)),
                'end'   => $this->formato($inicio->copy()->addMinutes(90)),
            ])
            ->assertStatus(422)
            ->assertJson(['message' => 'Ya existe otra reserva que se solapa con este horario.']);

        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_la_creacion_rapida_valida_que_el_fin_sea_posterior(): void
    {
        $court  = Court::factory()->create();
        $inicio = $this->inicio();

        $this->actingAs($this->manager())
            ->postJson(route('manager.reservations.quick.store', $court), [
                'start' => $this->formato($inicio),
                'end'   => $this->formato($inicio->copy()->subHour()),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('end');
    }

    // ───── Reprogramar (arrastrar en el calendario) ─────

    public function test_reprogramar_una_reserva_interna_a_un_hueco_libre(): void
    {
        $court   = Court::factory()->create();
        $inicio  = $this->inicio();
        $reserva = $this->interna($court, $inicio);

        // 18:00 -> 18:30: no debe chocar consigo misma
        $nuevo = $inicio->copy()->addMinutes(30);

        $this->actingAs($this->manager())
            ->patchJson(route('manager.reservations.reschedule', $reserva), [
                'start' => $this->formato($nuevo),
                'end'   => $this->formato($nuevo->copy()->addHour()),
            ])
            ->assertOk()
            ->assertJson(['message' => 'Reserva actualizada correctamente.']);

        $this->assertTrue($reserva->fresh()->start_time->equalTo($nuevo));
    }

    public function test_reprogramar_a_un_hueco_ocupado_se_rechaza_y_no_cambia_nada(): void
    {
        $court   = Court::factory()->create();
        $inicio  = $this->inicio();
        $reserva = $this->interna($court, $inicio);
        $this->interna($court, $inicio->copy()->addHours(2));

        $this->actingAs($this->manager())
            ->patchJson(route('manager.reservations.reschedule', $reserva), [
                'start' => $this->formato($inicio->copy()->addHours(2)),
                'end'   => $this->formato($inicio->copy()->addHours(3)),
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['message']);

        $this->assertTrue($reserva->fresh()->start_time->equalTo($inicio));
    }

    public function test_reprogramar_fuera_de_horario_se_rechaza(): void
    {
        $court   = Court::factory()->create();
        $inicio  = $this->inicio();
        $reserva = $this->interna($court, $inicio);
        $fuera   = $this->inicio(21, 30);

        $this->actingAs($this->manager())
            ->patchJson(route('manager.reservations.reschedule', $reserva), [
                'start' => $this->formato($fuera),
                'end'   => $this->formato($fuera->copy()->addHour()),
            ])
            ->assertStatus(422);

        $this->assertTrue($reserva->fresh()->start_time->equalTo($inicio));
    }

    public function test_reprogramar_una_reserva_de_cliente_a_media_hora_se_rechaza(): void
    {
        $court   = Court::factory()->create();
        $inicio  = $this->inicio();
        $reserva = Reservation::factory()->paid()->create([
            'court_id'   => $court->id,
            'start_time' => $inicio,
            'end_time'   => $inicio->copy()->addHour(),
        ]);

        $nuevo = $inicio->copy()->addMinutes(30);

        $this->actingAs($this->manager())
            ->patchJson(route('manager.reservations.reschedule', $reserva), [
                'start' => $this->formato($nuevo),
                'end'   => $this->formato($nuevo->copy()->addHour()),
            ])
            ->assertStatus(422);

        $this->assertTrue($reserva->fresh()->start_time->equalTo($inicio));
    }

    public function test_no_se_reprograma_una_reserva_cancelada(): void
    {
        $court   = Court::factory()->create();
        $inicio  = $this->inicio();
        $reserva = Reservation::factory()->canceled()->withoutUser()->create([
            'court_id'   => $court->id,
            'start_time' => $inicio,
            'end_time'   => $inicio->copy()->addHour(),
        ]);

        $nuevo = $inicio->copy()->addHours(2);

        $this->actingAs($this->manager())
            ->patchJson(route('manager.reservations.reschedule', $reserva), [
                'start' => $this->formato($nuevo),
                'end'   => $this->formato($nuevo->copy()->addHour()),
            ]);

        // Sea cual sea la respuesta, una reserva cancelada no debe moverse
        $this->assertTrue($reserva->fresh()->start_time->equalTo($inicio));
    }

    // ───── Editar ─────

    public function test_el_manager_ve_el_formulario_de_edicion(): void
    {
        $reserva = Reservation::factory()->create();

        $this->actingAs($this->manager())
            ->get(route('manager.reservations.edit', $reserva))
            ->assertOk();
    }

    public function test_no_se_puede_editar_una_reserva_cancelada_ni_reembolsada(): void
    {
        $manager = $this->manager();

        foreach ([PaymentStatus::Canceled, PaymentStatus::Refunded] as $estado) {
            $reserva = Reservation::factory()->create(['payment_status' => $estado]);

            $this->actingAs($manager)
                ->get(route('manager.reservations.edit', $reserva))
                ->assertRedirect(route('manager.reservations.index'))
                ->assertSessionHasErrors('reservation');
        }
    }

    public function test_no_se_puede_editar_una_reserva_pasada(): void
    {
        $pasado  = now()->subDays(2)->setTime(18, 0);
        $reserva = Reservation::factory()->paid()->create([
            'start_time' => $pasado,
            'end_time'   => $pasado->copy()->addHour(),
        ]);

        $this->actingAs($this->manager())
            ->get(route('manager.reservations.edit', $reserva))
            ->assertRedirect(route('manager.reservations.index'))
            ->assertSessionHasErrors('reservation');
    }

    public function test_actualizar_una_reserva_interna_exige_informacion(): void
    {
        $court   = Court::factory()->create();
        $reserva = $this->interna($court, $this->inicio());

        $this->actingAs($this->manager())
            ->put(route('manager.reservations.update', $reserva), [
                'date'            => $this->inicio()->format('Y-m-d'),
                'start_time_only' => '18:00',
                'end_time_only'   => '19:00',
            ])
            ->assertSessionHasErrors('information');
    }

    public function test_actualizar_una_reserva_de_cliente_no_exige_informacion(): void
    {
        $court   = Court::factory()->create();
        $inicio  = $this->inicio();
        $reserva = Reservation::factory()->paid()->create([
            'court_id'   => $court->id,
            'start_time' => $inicio,
            'end_time'   => $inicio->copy()->addHour(),
        ]);

        $this->actingAs($this->manager())
            ->put(route('manager.reservations.update', $reserva), [
                'date'            => $inicio->format('Y-m-d'),
                'start_time_only' => '19:00',
                'end_time_only'   => '20:00',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame('19:00', $reserva->fresh()->start_time->format('H:i'));
    }

    public function test_actualizar_a_un_hueco_ocupado_devuelve_error_y_no_cambia_nada(): void
    {
        $court   = Court::factory()->create();
        $inicio  = $this->inicio();
        $reserva = $this->interna($court, $inicio);
        $this->interna($court, $inicio->copy()->addHours(2));

        $this->actingAs($this->manager())
            ->from(route('manager.reservations.edit', $reserva))
            ->put(route('manager.reservations.update', $reserva), [
                'date'            => $inicio->format('Y-m-d'),
                'start_time_only' => '20:00',
                'end_time_only'   => '21:00',
                'information'     => 'Mismo texto',
            ])
            ->assertSessionHasErrors('start_time_only');

        $this->assertSame('18:00', $reserva->fresh()->start_time->format('H:i'));
    }

    public function test_actualizar_por_json_devuelve_422_si_falla(): void
    {
        $court   = Court::factory()->create();
        $inicio  = $this->inicio();
        $reserva = $this->interna($court, $inicio);
        $this->interna($court, $inicio->copy()->addHours(2));

        $this->actingAs($this->manager())
            ->putJson(route('manager.reservations.update', $reserva), [
                'date'            => $inicio->format('Y-m-d'),
                'start_time_only' => '20:00',
                'end_time_only'   => '21:00',
                'information'     => 'Mismo texto',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    public function test_no_se_actualiza_una_reserva_cancelada_enviando_el_put_directamente(): void
    {
        $court   = Court::factory()->create();
        $inicio  = $this->inicio();
        $reserva = Reservation::factory()->canceled()->withoutUser()->create([
            'court_id'   => $court->id,
            'start_time' => $inicio,
            'end_time'   => $inicio->copy()->addHour(),
        ]);

        $this->actingAs($this->manager())
            ->put(route('manager.reservations.update', $reserva), [
                'date'            => $inicio->format('Y-m-d'),
                'start_time_only' => '20:00',
                'end_time_only'   => '21:00',
                'information'     => 'Intento',
            ]);

        $this->assertSame('18:00', $reserva->fresh()->start_time->format('H:i'));
    }

    // ───── Lectura ─────

    public function test_show_devuelve_los_datos_de_la_reserva_en_json(): void
    {
        $client  = User::factory()->client()->create();
        $reserva = Reservation::factory()->paid()->create(['user_id' => $client->id]);

        $this->actingAs($this->manager())
            ->getJson(route('manager.reservations.show', $reserva))
            ->assertOk()
            ->assertJson([
                'id'             => $reserva->id,
                'client_email'   => $client->email,
                'payment_status' => 'paid',
                'is_internal'    => false,
            ]);
    }

    public function test_show_marca_como_interna_una_reserva_sin_cliente(): void
    {
        $reserva = Reservation::factory()->withoutUser()->create();

        $this->actingAs($this->manager())
            ->getJson(route('manager.reservations.show', $reserva))
            ->assertOk()
            ->assertJson(['client_email' => null, 'is_internal' => true]);
    }

    public function test_events_devuelve_json_para_el_calendario(): void
    {
        $court = Court::factory()->create();
        $this->interna($court, $this->inicio());

        $this->actingAs($this->manager())
            ->getJson(route('manager.courts.events', $court) . '?' . http_build_query([
                'start' => now()->startOfWeek()->addWeek()->toIso8601String(),
                'end'   => now()->endOfWeek()->addWeek()->toIso8601String(),
            ]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json');
    }

    public function test_un_client_no_accede_a_los_endpoints_json_del_manager(): void
    {
        $client  = User::factory()->client()->create();
        $reserva = Reservation::factory()->create();
        $court   = $reserva->court;

        $this->actingAs($client)->getJson(route('manager.reservations.show', $reserva))->assertForbidden();
        $this->actingAs($client)->getJson(route('manager.courts.events', $court))->assertForbidden();
        $this->actingAs($client)->postJson(route('manager.reservations.quick.store', $court), [])->assertForbidden();
    }

    public function test_no_se_puede_crear_una_reserva_en_el_pasado(): void
    {
        $court = Court::factory()->create();

        $this->actingAs($this->manager())
            ->post(route('manager.reservations.store'), $this->datosStore($court, [
                'date' => now()->subDay()->format('Y-m-d'),
            ]))
            ->assertSessionHasErrors('start_time_only');

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_la_creacion_rapida_rechaza_una_hora_fuera_de_horario(): void
    {
        $court  = Court::factory()->create();
        $inicio = $this->inicio(3, 0);

        $this->actingAs($this->manager())
            ->postJson(route('manager.reservations.quick.store', $court), [
                'start' => $this->formato($inicio),
                'end'   => $this->formato($inicio->copy()->addHour()),
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_la_creacion_rapida_nace_pagada_para_bloquear_el_horario(): void
    {
        $court  = Court::factory()->create();
        $inicio = $this->inicio();

        $this->actingAs($this->manager())
            ->postJson(route('manager.reservations.quick.store', $court), [
                'start' => $this->formato($inicio),
                'end'   => $this->formato($inicio->copy()->addHour()),
            ])
            ->assertOk();

        $this->assertDatabaseHas('reservations', ['court_id' => $court->id, 'payment_status' => 'paid']);
    }

    public function test_actualizar_una_reserva_interna_alargandola_no_choca_consigo_misma(): void
    {
        $court   = Court::factory()->create();
        $inicio  = $this->inicio();
        $reserva = $this->interna($court, $inicio);

        $this->actingAs($this->manager())
            ->put(route('manager.reservations.update', $reserva), [
                'date'            => $inicio->format('Y-m-d'),
                'start_time_only' => '18:00',
                'end_time_only'   => '20:00',   // 18:00-19:00 -> 18:00-20:00
                'information'     => 'Torneo ampliado',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('20:00', $reserva->fresh()->end_time->format('H:i'));
    }

    public function test_reprogramar_una_reserva_de_cliente_avisa_por_email(): void
    {
        Mail::fake();
        $court   = Court::factory()->create();
        $inicio  = $this->inicio();
        $reserva = Reservation::factory()->paid()->create([
            'court_id'   => $court->id,
            'start_time' => $inicio,
            'end_time'   => $inicio->copy()->addHour(),
        ]);

        $nuevo = $inicio->copy()->addHour(); // 19:00-20:00

        $this->actingAs($this->manager())
            ->patchJson(route('manager.reservations.reschedule', $reserva), [
                'start' => $this->formato($nuevo),
                'end'   => $this->formato($nuevo->copy()->addHour()),
            ])
            ->assertOk();

        Mail::assertQueued(ReservationRescheduledMail::class);
    }

    public function test_reprogramar_una_reserva_interna_no_envia_email(): void
    {
        Mail::fake();
        $court   = Court::factory()->create();
        $inicio  = $this->inicio();
        $reserva = $this->interna($court, $inicio);
        $nuevo   = $inicio->copy()->addHours(2);

        $this->actingAs($this->manager())
            ->patchJson(route('manager.reservations.reschedule', $reserva), [
                'start' => $this->formato($nuevo),
                'end'   => $this->formato($nuevo->copy()->addHour()),
            ])
            ->assertOk();

        Mail::assertNothingQueued();
    }

    public function test_si_la_reprogramacion_falla_no_se_envia_email(): void
    {
        Mail::fake();
        $court   = Court::factory()->create();
        $inicio  = $this->inicio();
        $reserva = Reservation::factory()->paid()->create([
            'court_id'   => $court->id,
            'start_time' => $inicio,
            'end_time'   => $inicio->copy()->addHour(),
        ]);
        $this->interna($court, $inicio->copy()->addHour());

        $this->actingAs($this->manager())
            ->patchJson(route('manager.reservations.reschedule', $reserva), [
                'start' => $this->formato($inicio->copy()->addHour()),
                'end'   => $this->formato($inicio->copy()->addHours(2)),
            ])
            ->assertStatus(422);

        Mail::assertNothingQueued();
    }

    public function test_actualizar_a_una_fecha_pasada_se_rechaza(): void
    {
        $court   = Court::factory()->create();
        $inicio  = $this->inicio();
        $reserva = $this->interna($court, $inicio);

        $ayer = now()->subDay()->format('Y-m-d');

        $this->actingAs($this->manager())
            ->from(route('manager.reservations.edit', $reserva))
            ->put(route('manager.reservations.update', $reserva), [
                'date'            => $ayer,
                'start_time_only' => '18:00',
                'end_time_only'   => '19:00',
                'information'     => 'Intento',
            ])
            ->assertSessionHasErrors('start_time_only');

        $this->assertTrue($reserva->fresh()->start_time->equalTo($inicio));
    }

    public function test_actualizar_fuera_del_horario_de_apertura_se_rechaza(): void
    {
        $court   = Court::factory()->create();
        $inicio  = $this->inicio();
        $reserva = $this->interna($court, $inicio);

        $this->actingAs($this->manager())
            ->from(route('manager.reservations.edit', $reserva))
            ->put(route('manager.reservations.update', $reserva), [
                'date'            => $inicio->format('Y-m-d'),
                'start_time_only' => '21:30',
                'end_time_only'   => '22:30',
                'information'     => 'Intento',
            ])
            ->assertSessionHasErrors('start_time_only');

        $this->assertSame('18:00', $reserva->fresh()->start_time->format('H:i'));
    }

    public function test_actualizar_una_reserva_de_cliente_a_media_hora_se_rechaza(): void
    {
        $court   = Court::factory()->create();
        $inicio  = $this->inicio();
        $reserva = Reservation::factory()->paid()->create([
            'court_id'   => $court->id,
            'start_time' => $inicio,
            'end_time'   => $inicio->copy()->addHour(),
        ]);

        $this->actingAs($this->manager())
            ->from(route('manager.reservations.edit', $reserva))
            ->put(route('manager.reservations.update', $reserva), [
                'date'            => $inicio->format('Y-m-d'),
                'start_time_only' => '18:30',
                'end_time_only'   => '19:30',
            ])
            ->assertSessionHasErrors('start_time_only');

        $this->assertSame('18:00', $reserva->fresh()->start_time->format('H:i'));
    }

    public function test_actualizar_una_reserva_de_cliente_a_dos_horas_se_rechaza(): void
    {
        $court   = Court::factory()->create();
        $inicio  = $this->inicio();
        $reserva = Reservation::factory()->paid()->create([
            'court_id'   => $court->id,
            'start_time' => $inicio,
            'end_time'   => $inicio->copy()->addHour(),
        ]);

        $this->actingAs($this->manager())
            ->from(route('manager.reservations.edit', $reserva))
            ->put(route('manager.reservations.update', $reserva), [
                'date'            => $inicio->format('Y-m-d'),
                'start_time_only' => '18:00',
                'end_time_only'   => '20:00',
            ])
            ->assertSessionHasErrors('end_time_only');

        $this->assertSame('19:00', $reserva->fresh()->end_time->format('H:i'));
    }
}