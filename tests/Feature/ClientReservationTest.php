<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_client_no_puede_cancelar_la_reserva_de_otro(): void
    {
        $dueno = User::factory()->client()->create();
        $otro  = User::factory()->client()->create();
        $reserva = Reservation::factory()->create(['user_id' => $dueno->id]);

        $this->actingAs($otro)
            ->patch(route('client.reservations.cancel', $reserva))
            ->assertForbidden();

        $this->assertDatabaseHas('reservations', [
            'id' => $reserva->id,
            'payment_status' => 'pending',
            'canceled_at' => null,
        ]);
    }

    public function test_un_client_puede_cancelar_su_propia_reserva(): void
    {
        $client = User::factory()->client()->create();
        $reserva = Reservation::factory()->create(['user_id' => $client->id]);

        $this->actingAs($client)
            ->patch(route('client.reservations.cancel', $reserva))
            ->assertRedirect();

        $this->assertDatabaseHas('reservations', [
            'id' => $reserva->id,
            'payment_status' => 'canceled',
            'canceled_by' => 'client',
        ]);

        $this->assertNotNull($reserva->fresh()->canceled_at);
    }
}