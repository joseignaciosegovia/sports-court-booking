<?php

namespace Tests\Feature;

use App\Enums\CanceledBy;
use App\Enums\PaymentStatus;
use App\Models\Reservation;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelExpiredReservationsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_comando_cancela_solo_las_reservas_caducadas(): void
    {
        $caducada = Reservation::factory()->expired()->create();
        $vigente  = Reservation::factory()->create();
        $pagada   = Reservation::factory()->paid()->create(['expires_at' => now()->subHour()]);

        $this->artisan('reservations:cancel-expired')
            ->expectsOutput('1 reservas expiradas canceladas.')
            ->assertSuccessful();

        $this->assertSame(PaymentStatus::Canceled, $caducada->fresh()->payment_status);
        $this->assertSame(CanceledBy::System, $caducada->fresh()->canceled_by);
        $this->assertSame(PaymentStatus::Pending, $vigente->fresh()->payment_status);
        $this->assertSame(PaymentStatus::Paid, $pagada->fresh()->payment_status);
    }

    public function test_sin_reservas_caducadas_el_comando_termina_bien(): void
    {
        $this->artisan('reservations:cancel-expired')
            ->expectsOutput('0 reservas expiradas canceladas.')
            ->assertSuccessful();
    }

    public function test_el_comando_esta_programado_cada_minuto(): void
    {
        $evento = collect(app(Schedule::class)->events())
            ->first(fn ($e) => str_contains($e->command, 'reservations:cancel-expired'));

        $this->assertNotNull($evento, 'El comando no está programado');
        $this->assertSame('* * * * *', $evento->expression);
    }
}