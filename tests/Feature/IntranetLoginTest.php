<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class IntranetLoginTest extends TestCase
{
    use RefreshDatabase;

    private function intentar(string $email, string $password)
    {
        return Volt::test('pages.auth.intranet-login')
            ->set('email', $email)
            ->set('password', $password)
            ->call('login');
    }

    public function test_la_pantalla_se_muestra_a_los_invitados(): void
    {
        $this->get(route('intranet.login'))
            ->assertOk()
            ->assertSeeVolt('pages.auth.intranet-login');
    }

    public function test_un_manager_entra_y_va_a_su_panel(): void
    {
        $manager = User::factory()->manager()->create();

        $this->intentar($manager->email, 'password')
            ->assertHasNoErrors()
            ->assertRedirect(route('manager.dashboard'));

        $this->assertAuthenticatedAs($manager);
    }

    public function test_un_admin_entra_y_va_a_su_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $this->intentar($admin->email, 'password')
            ->assertHasNoErrors()
            ->assertRedirect(route('manager.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_un_client_es_rechazado_y_no_queda_autenticado(): void
    {
        $client = User::factory()->client()->create();

        $this->intentar($client->email, 'password')
            ->assertHasErrors('email')
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_una_contrasena_incorrecta_se_rechaza(): void
    {
        $manager = User::factory()->manager()->create();

        $this->intentar($manager->email, 'equivocada')
            ->assertHasErrors('email')
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_exige_email_valido_y_contrasena(): void
    {
        Volt::test('pages.auth.intranet-login')
            ->set('email', 'no-es-un-email')
            ->set('password', '')
            ->call('login')
            ->assertHasErrors(['email', 'password']);
    }

    // ───── Límite de intentos (fallan hasta aplicar el arreglo) ─────

    public function test_tras_5_fallos_se_bloquea_incluso_con_la_contrasena_correcta(): void
    {
        $manager = User::factory()->manager()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->intentar($manager->email, 'equivocada');
        }

        $this->intentar($manager->email, 'password')
            ->assertHasErrors('email')
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_con_4_fallos_todavia_se_puede_entrar(): void
    {
        $manager = User::factory()->manager()->create();

        for ($i = 0; $i < 4; $i++) {
            $this->intentar($manager->email, 'equivocada');
        }

        $this->intentar($manager->email, 'password')
            ->assertHasNoErrors()
            ->assertRedirect(route('manager.dashboard'));
    }

    public function test_un_client_que_prueba_su_contrasena_gasta_intentos(): void
    {
        $client = User::factory()->client()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->intentar($client->email, 'password');   // correcta, pero zona prohibida
        }

        $this->intentar($client->email, 'password')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    // ───── Acceso ─────

    public function test_un_usuario_autenticado_no_ve_el_login_de_la_intranet(): void
    {
        $this->actingAs(User::factory()->manager()->create())
            ->get(route('intranet.login'))
            ->assertRedirect(route('manager.dashboard'));
    }

    public function test_el_checkbox_recordar_esta_enlazado_a_la_propiedad_correcta(): void
    {
        $this->get(route('intranet.login'))
            ->assertSee('wire:model="remember"', false)
            ->assertDontSee('form.remember', false);
    }
}