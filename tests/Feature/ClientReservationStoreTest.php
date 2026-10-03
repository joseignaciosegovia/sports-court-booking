<?php

namespace Tests\Feature;

use App\Models\Court;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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

        $this->actingAs($client)->post(route('client.reservations.store'), $datos);

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

        $this->actingAs($client)->post(route('client.reservations.store'), $datos);

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
}