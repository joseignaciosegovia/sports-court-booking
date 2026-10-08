<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Volt\Volt;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    private function intentar(string $email, string $password)
    {
        return Volt::test('pages.auth.login')
            ->set('form.email', $email)
            ->set('form.password', $password)
            ->call('login');
    }

    public function test_un_fallo_de_login_no_autentica_y_muestra_error(): void
    {
        $user = User::factory()->client()->create();

        $this->intentar($user->email, 'equivocada')
            ->assertHasErrors('form.email')
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_tras_5_fallos_se_bloquea_incluso_con_la_contrasena_correcta(): void
    {
        Event::fake([Lockout::class]);
        $user = User::factory()->client()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->intentar($user->email, 'equivocada');
        }

        $this->intentar($user->email, 'password')   // la correcta
            ->assertHasErrors('form.email')
            ->assertNoRedirect();

        $this->assertGuest();
        Event::assertDispatched(Lockout::class);
    }

    public function test_con_4_fallos_todavia_se_puede_entrar(): void
    {
        $user = User::factory()->client()->create();

        for ($i = 0; $i < 4; $i++) {
            $this->intentar($user->email, 'equivocada');
        }

        $this->intentar($user->email, 'password')
            ->assertHasNoErrors()
            ->assertRedirect(route('client.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_un_login_correcto_reinicia_el_contador(): void
    {
        $user = User::factory()->client()->create();

        for ($i = 0; $i < 4; $i++) {
            $this->intentar($user->email, 'equivocada');
        }
        $this->intentar($user->email, 'password');   // acierta: limpia el contador
        auth()->logout();

        // 4 fallos más no deben bloquear, porque el contador volvió a 0
        for ($i = 0; $i < 4; $i++) {
            $this->intentar($user->email, 'equivocada');
        }

        $this->intentar($user->email, 'password')->assertHasNoErrors();
    }

    public function test_el_bloqueo_de_un_email_no_afecta_a_otro(): void
    {
        $bloqueado = User::factory()->client()->create();
        $otro      = User::factory()->client()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->intentar($bloqueado->email, 'equivocada');
        }

        $this->intentar($otro->email, 'password')->assertHasNoErrors();
    }

    public function test_el_bloqueo_no_distingue_mayusculas_en_el_email(): void
    {
        $user = User::factory()->client()->create(['email' => 'ana@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->intentar('ANA@example.com', 'equivocada');
        }

        $this->intentar('ana@example.com', 'password')
            ->assertHasErrors('form.email');
    }

    public function test_el_login_exige_email_valido_y_contrasena(): void
    {
        Volt::test('pages.auth.login')
            ->set('form.email', 'no-es-un-email')
            ->set('form.password', '')
            ->call('login')
            ->assertHasErrors(['form.email', 'form.password']);
    }

    // ───── Redirección por rol ─────

    public function test_cada_rol_va_a_su_panel_tras_el_login(): void
    {
        $this->intentar(User::factory()->manager()->create(['email' => 'm@example.com'])->email, 'password')
            ->assertRedirect(route('manager.dashboard'));

        auth()->logout();

        $this->intentar(User::factory()->admin()->create(['email' => 'a@example.com'])->email, 'password')
            ->assertRedirect(route('manager.dashboard'));
    }
}