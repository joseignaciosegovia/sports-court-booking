<?php

namespace Tests\Feature\Models;

use App\Enums\PaymentStatus;
use App\Models\Court;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    private function inicio(): \Carbon\Carbon
    {
        return now()->addDays(3)->setTime(18, 0, 0);
    }

    // -------------------------------------------------------------------------
    // blocking()
    // -------------------------------------------------------------------------

    public function test_una_reserva_pagada_bloquea_la_franja(): void
    {
        $reservation = Reservation::factory()
            ->paid()
            ->create();

        $this->assertTrue(
            Reservation::query()
                ->blocking()
                ->whereKey($reservation->id)
                ->exists()
        );
    }

    public function test_una_reserva_pendiente_no_expirada_bloquea_la_franja(): void
    {
        $reservation = Reservation::factory()->create([
            'payment_status' => PaymentStatus::Pending,
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->assertTrue(
            Reservation::query()
                ->blocking()
                ->whereKey($reservation->id)
                ->exists()
        );
    }

    public function test_una_reserva_pendiente_expirada_no_bloquea_la_franja(): void
    {
        $reservation = Reservation::factory()
            ->expired()
            ->create([
                'payment_status' => PaymentStatus::Pending,
            ]);

        $this->assertFalse(
            Reservation::query()
                ->blocking()
                ->whereKey($reservation->id)
                ->exists()
        );
    }

    public function test_una_reserva_cancelada_no_bloquea_la_franja(): void
    {
        $reservation = Reservation::factory()
            ->canceled()
            ->create();

        $this->assertFalse(
            Reservation::query()
                ->blocking()
                ->whereKey($reservation->id)
                ->exists()
        );
    }

    public function test_una_reserva_pendiente_que_expira_exactamente_ahora_no_bloquea(): void
    {
        $reservation = Reservation::factory()->create([
            'payment_status' => PaymentStatus::Pending,
            'expires_at' => now(),
        ]);

        $this->assertFalse(
            Reservation::query()
                ->blocking()
                ->whereKey($reservation->id)
                ->exists()
        );
    }

    // -------------------------------------------------------------------------
    // hasOverlap()
    // -------------------------------------------------------------------------

    public function test_has_overlap_detecta_una_reserva_que_se_solapa(): void
    {
        $court = Court::factory()->create();

        $inicio = $this->inicio();

        Reservation::factory()->paid()->create([
            'court_id' => $court->id,
            'start_time' => $inicio,
            'end_time' => $inicio->copy()->addHour(),
        ]);

        $this->assertTrue(
            Reservation::hasOverlap(
                $court->id,
                $inicio->copy()->addMinutes(30)->toDateTimeString(),
                $inicio->copy()->addMinutes(90)->toDateTimeString(),
            )
        );
    }

    public function test_has_overlap_devuelve_false_si_no_hay_solapamiento(): void
    {
        $court = Court::factory()->create();

        $inicio = $this->inicio();

        Reservation::factory()->paid()->create([
            'court_id' => $court->id,
            'start_time' => $inicio,
            'end_time' => $inicio->copy()->addHour(),
        ]);

        $this->assertFalse(
            Reservation::hasOverlap(
                $court->id,
                $inicio->copy()->addHour()->toDateTimeString(),
                $inicio->copy()->addHours(2)->toDateTimeString(),
            )
        );
    }

    public function test_has_overlap_no_considera_reservas_de_otra_pista(): void
    {
        $court1 = Court::factory()->create();
        $court2 = Court::factory()->create();

        $inicio = $this->inicio();

        Reservation::factory()->paid()->create([
            'court_id' => $court1->id,
            'start_time' => $inicio,
            'end_time' => $inicio->copy()->addHour(),
        ]);

        $this->assertFalse(
            Reservation::hasOverlap(
                $court2->id,
                $inicio->copy()->addMinutes(30)->toDateTimeString(),
                $inicio->copy()->addMinutes(90)->toDateTimeString(),
            )
        );
    }

    public function test_has_overlap_ignora_la_reserva_indicada(): void
    {
        $court = Court::factory()->create();

        $inicio = $this->inicio();

        $reservation = Reservation::factory()->paid()->create([
            'court_id' => $court->id,
            'start_time' => $inicio,
            'end_time' => $inicio->copy()->addHour(),
        ]);

        $this->assertFalse(
            Reservation::hasOverlap(
                $court->id,
                $inicio->copy()->addMinutes(30)->toDateTimeString(),
                $inicio->copy()->addMinutes(90)->toDateTimeString(),
                $reservation->id,
            )
        );
    }

    public function test_has_overlap_ignora_una_reserva_pendiente_expirada(): void
    {
        $court = Court::factory()->create();

        $inicio = $this->inicio();

        Reservation::factory()
            ->expired()
            ->create([
                'court_id' => $court->id,
                'payment_status' => PaymentStatus::Pending,
                'start_time' => $inicio,
                'end_time' => $inicio->copy()->addHour(),
            ]);

        $this->assertFalse(
            Reservation::hasOverlap(
                $court->id,
                $inicio->copy()->addMinutes(30)->toDateTimeString(),
                $inicio->copy()->addMinutes(90)->toDateTimeString(),
            )
        );
    }

    public function test_has_overlap_no_considera_una_reserva_cancelada(): void
    {
        $court = Court::factory()->create();

        $inicio = $this->inicio();

        Reservation::factory()
            ->canceled()
            ->create([
                'court_id' => $court->id,
                'start_time' => $inicio,
                'end_time' => $inicio->copy()->addHour(),
            ]);

        $this->assertFalse(
            Reservation::hasOverlap(
                $court->id,
                $inicio->copy()->addMinutes(30)->toDateTimeString(),
                $inicio->copy()->addMinutes(90)->toDateTimeString(),
            )
        );
    }

    public function test_has_overlap_detecta_una_reserva_pendiente_no_expirada(): void
    {
        $court = Court::factory()->create();

        $inicio = $this->inicio();

        Reservation::factory()->create([
            'court_id' => $court->id,
            'payment_status' => PaymentStatus::Pending,
            'expires_at' => now()->addMinutes(10),
            'start_time' => $inicio,
            'end_time' => $inicio->copy()->addHour(),
        ]);

        $this->assertTrue(
            Reservation::hasOverlap(
                $court->id,
                $inicio->copy()->addMinutes(30)->toDateTimeString(),
                $inicio->copy()->addMinutes(90)->toDateTimeString(),
            )
        );
    }

    // -------------------------------------------------------------------------
    // Límites del solapamiento
    // -------------------------------------------------------------------------

    public function test_has_overlap_no_considera_solapadas_dos_reservas_contiguas(): void
    {
        $court = Court::factory()->create();

        $inicio = $this->inicio();

        Reservation::factory()->paid()->create([
            'court_id' => $court->id,
            'start_time' => $inicio,
            'end_time' => $inicio->copy()->addHour(),
        ]);

        // Existente: 18:00 - 19:00
        // Nueva:     19:00 - 20:00
        $this->assertFalse(
            Reservation::hasOverlap(
                $court->id,
                $inicio->copy()->addHour()->toDateTimeString(),
                $inicio->copy()->addHours(2)->toDateTimeString(),
            )
        );
    }

    public function test_has_overlap_detecta_si_la_nueva_reserva_empieza_antes_y_termina_dentro(): void
    {
        $court = Court::factory()->create();

        $inicio = $this->inicio();

        Reservation::factory()->paid()->create([
            'court_id' => $court->id,
            'start_time' => $inicio->copy()->addMinutes(30),
            'end_time' => $inicio->copy()->addMinutes(90),
        ]);

        $this->assertTrue(
            Reservation::hasOverlap(
                $court->id,
                $inicio,
                $inicio->copy()->addHour(),
            )
        );
    }

    public function test_has_overlap_detecta_si_la_nueva_reserva_contiene_completamente_a_la_existente(): void
    {
        $court = Court::factory()->create();

        $inicio = $this->inicio();

        Reservation::factory()->paid()->create([
            'court_id' => $court->id,
            'start_time' => $inicio->copy()->addMinutes(15),
            'end_time' => $inicio->copy()->addMinutes(45),
        ]);

        $this->assertTrue(
            Reservation::hasOverlap(
                $court->id,
                $inicio,
                $inicio->copy()->addHour(),
            )
        );
    }
}