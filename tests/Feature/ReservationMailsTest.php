<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Mail\ReservationCanceledByManagerMail;
use App\Mail\ReservationRescheduledMail;
use App\Models\Court;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationMailsTest extends TestCase
{
    use RefreshDatabase;

    private function reserva(bool $pagada = false): Reservation
    {
        $cliente = User::factory()->client()->create(['name' => 'Ana Cliente']);
        $court   = Court::factory()->create(['name' => 'Pista 7']);
        $inicio  = now()->addDays(3)->setTime(18, 0);

        $factory = $pagada ? Reservation::factory()->paid() : Reservation::factory();

        return $factory->create([
            'user_id'    => $cliente->id,
            'court_id'   => $court->id,
            'start_time' => $inicio,
            'end_time'   => $inicio->copy()->addHour(),
        ]);
    }

    public function test_el_mail_de_reprogramacion_se_renderiza_con_los_datos(): void
    {
        $reserva = $this->reserva(true);
        $antes   = $reserva->start_time->copy()->subDay();

        $mail = new ReservationRescheduledMail($reserva, $antes);

        $mail->assertHasSubject('Su reserva ha cambiado de horario');
        $mail->assertSeeInHtml('Ana Cliente');
        $mail->assertSeeInHtml('Pista 7');
        $mail->assertSeeInHtml($antes->format('d/m/Y H:i'));
        $mail->assertSeeInHtml($reserva->start_time->format('d/m/Y H:i'));
        $mail->assertSeeInHtml($reserva->end_time->format('H:i'));
    }

    public function test_el_mail_de_cancelacion_con_reembolso(): void
    {
        $reserva = $this->reserva(true);
        $reserva->update(['payment_status' => PaymentStatus::Refunded]);

        $mail = new ReservationCanceledByManagerMail($reserva->fresh(), 'Pista en obras');

        $mail->assertHasSubject('Su reserva ha sido cancelada');
        $mail->assertSeeInHtml('Ana Cliente');
        $mail->assertSeeInHtml('Pista 7');
        $mail->assertSeeInHtml('Pista en obras');
        $mail->assertSeeInHtml('Se le reembolsará');
        $mail->assertDontSeeInHtml('no es necesario hacer ninguna devolución');
    }

    public function test_el_mail_de_cancelacion_sin_pago(): void
    {
        $reserva = $this->reserva();
        $reserva->update(['payment_status' => PaymentStatus::Canceled]);

        $mail = new ReservationCanceledByManagerMail($reserva->fresh(), 'Motivo');

        $mail->assertSeeInHtml('no es necesario hacer ninguna devolución');
        $mail->assertDontSeeInHtml('Se le reembolsará');
    }
}