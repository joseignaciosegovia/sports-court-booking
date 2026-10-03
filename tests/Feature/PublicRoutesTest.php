<?php

namespace Tests\Feature;

use App\Models\Court;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_home_carga(): void
    {
        $this->get(route('home'))->assertOk();
    }

    public function test_el_login_de_intranet_carga_para_invitados(): void
    {
        $this->get(route('intranet.login'))->assertOk();
    }

    public function test_un_usuario_logueado_no_ve_el_login_de_intranet(): void
    {
        $this->actingAs(User::factory()->client()->create())
             ->get(route('intranet.login'))
             ->assertRedirect(); // el middleware guest lo redirige
    }

    public function test_los_horarios_publicos_de_una_pista_cargan(): void
    {
        $court = Court::factory()->create();

        $this->get(route('public.courts.schedule', $court))->assertOk();
    }

    public function test_el_registro_valida_los_datos(): void
    {
        $this->post(route('register'), [])->assertSessionHasErrors();
    }

    public function test_el_webhook_de_stripe_rechaza_peticiones_sin_firma(): void
    {
        // Stripe no manda CSRF, pero sí firma. Sin firma válida no debe aceptarse.
        $this->postJson(route('stripe.webhook'), ['type' => 'test'])
             ->assertStatus(400); // ajusta al código que devuelva tu controlador
    }
}
