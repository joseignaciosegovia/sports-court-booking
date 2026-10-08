<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function datos(array $extra = []): array
    {
        return [
            'name'                  => 'Ana Cliente',
            'email'                 => 'ana@example.com',
            'password'              => 'secreto123',
            'password_confirmation' => 'secreto123',
            'dni'                   => '12345678Z',
            'phone'                 => '600123456',
            ...$extra,
        ];
    }

    public function test_un_visitante_se_registra_y_queda_autenticado(): void
    {
        $this->post(route('register'), $this->datos())
            ->assertRedirect(route('client.dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'ana@example.com',
            'dni'   => '12345678Z',
            'phone' => '600123456',
        ]);
    }

    public function test_el_usuario_nace_como_client_aunque_envie_otro_rol(): void
    {
        $this->post(route('register'), $this->datos(['role' => 'admin']))
            ->assertRedirect(route('client.dashboard'));

        $usuario = User::where('email', 'ana@example.com')->firstOrFail();

        // Falla con un 500 si la columna role no tiene DEFAULT 'client'
        $this->assertSame('client', $usuario->role->value);
    }

    public function test_la_contrasena_se_guarda_hasheada_una_sola_vez(): void
    {
        $this->post(route('register'), $this->datos());

        $usuario = User::where('email', 'ana@example.com')->firstOrFail();

        $this->assertNotSame('secreto123', $usuario->password);
        $this->assertTrue(Hash::check('secreto123', $usuario->password));
    }

    public function test_se_envia_el_email_de_verificacion_y_la_cuenta_nace_sin_verificar(): void
    {
        Notification::fake();

        $this->post(route('register'), $this->datos());

        $usuario = User::where('email', 'ana@example.com')->firstOrFail();

        $this->assertNull($usuario->email_verified_at);
        Notification::assertSentTo($usuario, VerifyEmail::class);
    }

    public function test_un_usuario_sin_verificar_es_enviado_a_verificar_su_email(): void
    {
        $this->post(route('register'), $this->datos());

        $this->get(route('client.dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_el_registro_exige_los_campos_obligatorios(): void
    {
        $this->post(route('register'), [])
            ->assertSessionHasErrors(['name', 'email', 'password', 'dni']);

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_el_telefono_y_la_foto_son_opcionales(): void
    {
        $this->post(route('register'), $this->datos(['phone' => null]))
            ->assertRedirect(route('client.dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'ana@example.com', 'phone' => null]);
    }

    public function test_la_contrasena_debe_confirmarse_y_tener_8_caracteres(): void
    {
        $this->post(route('register'), $this->datos(['password_confirmation' => 'otra']))
            ->assertSessionHasErrors('password');

        $this->post(route('register'), $this->datos(['password' => '1234567', 'password_confirmation' => '1234567']))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_el_dni_debe_ser_valido(): void
    {
        // 12345678 corresponde a la letra Z, no a la A
        $this->post(route('register'), $this->datos(['dni' => '12345678A']))
            ->assertSessionHasErrors('dni');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_no_se_repite_el_email(): void
    {
        User::factory()->client()->create(['email' => 'ana@example.com']);

        $this->post(route('register'), $this->datos(['dni' => '00000000T']))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_no_se_repite_el_dni(): void
    {
        User::factory()->client()->create(['dni' => '12345678Z']);

        $this->post(route('register'), $this->datos(['email' => 'otra@example.com']))
            ->assertSessionHasErrors('dni');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_el_email_de_un_usuario_borrado_da_error_de_validacion_y_no_un_500(): void
    {
        User::factory()->client()->create(['email' => 'ana@example.com'])->delete();

        $this->post(route('register'), $this->datos(['dni' => '00000000T']))
            ->assertSessionHasErrors('email');
    }

    public function test_la_foto_debe_ser_una_imagen(): void
    {
        $this->post(route('register'), $this->datos([
            'photo' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ]))->assertSessionHasErrors('photo');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_se_guarda_la_foto_de_perfil(): void
    {
        Storage::fake('public');

        $this->post(route('register'), $this->datos([
            'photo' => UploadedFile::fake()->image('foto.jpg'),
        ]))->assertRedirect(route('client.dashboard'));

        $usuario = User::where('email', 'ana@example.com')->firstOrFail();

        $this->assertNotNull($usuario->photo);
        Storage::disk('public')->assertExists($usuario->photo);
    }

    public function test_un_usuario_ya_autenticado_no_puede_registrar_otra_cuenta(): void
    {
        $existente = User::factory()->client()->create();

        $this->actingAs($existente)
            ->post(route('register'), $this->datos())
            ->assertRedirect();

        // Falla hasta que añadas el middleware 'guest' a la ruta
        $this->assertDatabaseMissing('users', ['email' => 'ana@example.com']);
        $this->assertAuthenticatedAs($existente);
    }

    public function test_un_manager_logueado_que_visita_el_login_va_a_su_panel(): void
    {
        $this->actingAs(User::factory()->manager()->create())
            ->get(route('intranet.login'))
            ->assertRedirect(route('manager.dashboard'));
    }
}