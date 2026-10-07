<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Enums\PaymentStatus;
use App\Services\Common\ReservationCancellationService;
use Mockery;
use Stripe\Refund;

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

    private function fingirReembolso(): void
    {
        $mock = Mockery::mock(ReservationCancellationService::class)
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $mock->shouldReceive('createRefund')->andReturn(Refund::constructFrom(['id' => 're_test_1']));

        $this->app->instance(ReservationCancellationService::class, $mock);
    }

    public function test_cancelar_con_antelacion_reembolsa_y_avisa(): void
    {
        $client = User::factory()->client()->create();
        $reserva = Reservation::factory()->paid()->create([
            'user_id'    => $client->id,
            'start_time' => now()->addDays(2),
            'end_time'   => now()->addDays(2)->addHour(),
        ]);
        $this->fingirReembolso();

        $this->actingAs($client)
            ->patch(route('client.reservations.cancel', $reserva))
            ->assertRedirect(route('client.reservations.index'))
            ->assertSessionHas('success', 'Reserva cancelada. Se le devolverá el dinero.');

        $this->assertSame(PaymentStatus::Refunded, $reserva->fresh()->payment_status);
    }

    public function test_cancelar_tarde_no_reembolsa_y_lo_dice(): void
    {
        $client = User::factory()->client()->create();
        $reserva = Reservation::factory()->paid()->create([
            'user_id'    => $client->id,
            'start_time' => now()->addHours(5),
            'end_time'   => now()->addHours(6),
        ]);

        $this->actingAs($client)
            ->patch(route('client.reservations.cancel', $reserva))
            ->assertRedirect(route('client.reservations.index'))
            ->assertSessionHas('success');

        $this->assertSame(PaymentStatus::Canceled, $reserva->fresh()->payment_status);
    }

    public function test_si_el_reembolso_falla_se_muestra_el_error(): void
    {
        $client = User::factory()->client()->create();
        $reserva = Reservation::factory()->paid()->create([
            'user_id'    => $client->id,
            'start_time' => now()->addDays(2),
            'end_time'   => now()->addDays(2)->addHour(),
        ]);

        $mock = Mockery::mock(ReservationCancellationService::class)
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $mock->shouldReceive('createRefund')->andThrow(new \RuntimeException('Stripe caído'));
        $this->app->instance(ReservationCancellationService::class, $mock);

        $this->actingAs($client)
            ->from(route('client.reservations.index'))
            ->patch(route('client.reservations.cancel', $reserva))
            ->assertRedirect(route('client.reservations.index'))
            ->assertSessionHasErrors('reservation');

        $this->assertSame(PaymentStatus::Paid, $reserva->fresh()->payment_status);
    }

    public function test_no_se_puede_cancelar_una_reserva_ya_reembolsada(): void
    {
        $client = User::factory()->client()->create();
        $reserva = Reservation::factory()->create([
            'user_id'        => $client->id,
            'payment_status' => PaymentStatus::Refunded,
        ]);

        $this->actingAs($client)
            ->from(route('client.reservations.index'))
            ->patch(route('client.reservations.cancel', $reserva))
            ->assertRedirect(route('client.reservations.index'))
            ->assertSessionHasErrors('reservation');

        $this->assertSame(PaymentStatus::Refunded, $reserva->fresh()->payment_status);
    }
}