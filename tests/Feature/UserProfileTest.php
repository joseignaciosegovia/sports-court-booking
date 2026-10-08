<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    /** [rol, prefijo de ruta] */
    public static function roles(): array
    {
        return [
            'client'  => ['client', 'client'],
            'manager' => ['manager', 'manager'],
            'admin'   => ['admin', 'admin'],
        ];
    }

    private function usuario(string $rol, array $extra = []): User
    {
        return User::factory()->{$rol}()->create(['dni' => '12345678Z', ...$extra]);
    }

    private function datos(array $extra = []): array
    {
        return [
            'name'  => 'Nombre Nuevo',
            'dni'   => '12345678Z',
            'phone' => '611222333',
            ...$extra,
        ];
    }

    #[DataProvider('roles')]
    public function test_cada_rol_ve_su_perfil(string $rol, string $prefijo): void
    {
        $this->actingAs($this->usuario($rol))
            ->get(route("$prefijo.profile.edit"))
            ->assertOk();
    }

    #[DataProvider('roles')]
    public function test_cada_rol_actualiza_sus_datos(string $rol, string $prefijo): void
    {
        $user = $this->usuario($rol);

        $this->actingAs($user)
            ->put(route("$prefijo.profile.update"), $this->datos())
            ->assertRedirect(route("$prefijo.profile.edit"))
            ->assertSessionHas('success', 'Perfil actualizado correctamente.');

        $user->refresh();
        $this->assertSame('Nombre Nuevo', $user->name);
        $this->assertSame('611222333', $user->phone);
    }

    public function test_no_se_puede_cambiar_el_rol_ni_el_email_por_el_perfil(): void
    {
        $user = $this->usuario('client');
        $email = $user->email;

        $this->actingAs($user)
            ->put(route('client.profile.update'), $this->datos([
                'role'  => 'admin',
                'email' => 'otro@example.com',
            ]))
            ->assertRedirect();

        $user->refresh();
        $this->assertSame('client', $user->role->value);
        $this->assertSame($email, $user->email);
    }

    public function test_el_perfil_exige_nombre_y_dni(): void
    {
        $this->actingAs($this->usuario('client'))
            ->put(route('client.profile.update'), [])
            ->assertSessionHasErrors(['name', 'dni']);
    }

    public function test_el_dni_debe_ser_valido(): void
    {
        $user = $this->usuario('client');

        $this->actingAs($user)
            ->put(route('client.profile.update'), $this->datos(['dni' => '12345678A']))
            ->assertSessionHasErrors('dni');

        $this->assertSame('12345678Z', $user->fresh()->dni);
    }

    public function test_no_se_puede_usar_el_dni_de_otro_usuario(): void
    {
        User::factory()->client()->create(['dni' => '00000000T']);
        $user = $this->usuario('client');

        $this->actingAs($user)
            ->put(route('client.profile.update'), $this->datos(['dni' => '00000000T']))
            ->assertSessionHasErrors('dni');   // sin la regla unique da un 500

        $this->assertSame('12345678Z', $user->fresh()->dni);
    }

    public function test_conservar_el_propio_dni_no_da_error(): void
    {
        $this->actingAs($this->usuario('client'))
            ->put(route('client.profile.update'), $this->datos())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('client.profile.edit'));
    }

    // ───── Contraseña ─────

    public function test_se_puede_cambiar_la_contrasena(): void
    {
        $user = $this->usuario('client');

        $this->actingAs($user)
            ->put(route('client.profile.update'), $this->datos([
                'current_password' => 'password',
                'password' => 'nueva-clave-1',
                'password_confirmation' => 'nueva-clave-1',
            ]))
            ->assertRedirect(route('client.profile.edit'));

        $this->assertTrue(Hash::check('nueva-clave-1', $user->fresh()->password));
    }

    public function test_cambiar_la_contrasena_exige_la_actual(): void
    {
        $user = $this->usuario('client');

        $this->actingAs($user)
            ->put(route('client.profile.update'), $this->datos([
                'password'              => 'nueva-clave-1',
                'password_confirmation' => 'nueva-clave-1',
            ]))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_una_contrasena_actual_incorrecta_se_rechaza(): void
    {
        $user = $this->usuario('client');

        $this->actingAs($user)
            ->put(route('client.profile.update'), $this->datos([
                'current_password'      => 'equivocada',
                'password'              => 'nueva-clave-1',
                'password_confirmation' => 'nueva-clave-1',
            ]))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_dejar_la_contrasena_vacia_no_la_cambia(): void
    {
        $user = $this->usuario('client');

        $this->actingAs($user)
            ->put(route('client.profile.update'), $this->datos(['password' => null]))
            ->assertRedirect(route('client.profile.edit'));

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_la_contrasena_debe_confirmarse_y_tener_8_caracteres(): void
    {
        $user = $this->usuario('client');

        $this->actingAs($user)
            ->put(route('client.profile.update'), $this->datos(['password' => 'nueva-clave-1', 'password_confirmation' => 'otra']))
            ->assertSessionHasErrors('password');

        $this->actingAs($user)
            ->put(route('client.profile.update'), $this->datos(['password' => '1234567', 'password_confirmation' => '1234567']))
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    // ───── Foto (dependen de ProfileService) ─────

    public function test_la_foto_debe_ser_una_imagen(): void
    {
        $this->actingAs($this->usuario('client'))
            ->put(route('client.profile.update'), $this->datos([
                'photo' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('photo');
    }

    public function test_se_sube_una_foto_y_reemplaza_a_la_anterior(): void
    {
        Storage::fake('public');
        $user = $this->usuario('client');
        $user->photo = UploadedFile::fake()->image('vieja.jpg')->store('profiles', 'public');
        $user->save();
        $vieja = $user->photo;

        $this->actingAs($user)
            ->put(route('client.profile.update'), $this->datos([
                'photo' => UploadedFile::fake()->image('nueva.jpg'),
            ]))
            ->assertRedirect(route('client.profile.edit'));

        $user->refresh();
        $this->assertNotSame($vieja, $user->photo);
        Storage::disk('public')->assertExists($user->photo);
        Storage::disk('public')->assertMissing($vieja);
    }

    public function test_se_puede_eliminar_la_foto(): void
    {
        Storage::fake('public');
        $user = $this->usuario('client');
        $user->photo = UploadedFile::fake()->image('foto.jpg')->store('profiles', 'public');
        $user->save();
        $ruta = $user->photo;

        $this->actingAs($user)
            ->put(route('client.profile.update'), $this->datos(['delete_photo' => true]))
            ->assertRedirect(route('client.profile.edit'));

        $this->assertNull($user->fresh()->photo);
        Storage::disk('public')->assertMissing($ruta);
    }

    // ───── Acceso ─────

    public function test_un_invitado_va_al_login(): void
    {
        $this->put(route('client.profile.update'), $this->datos())->assertRedirect(route('login'));
        $this->put(route('manager.profile.update'), $this->datos())->assertRedirect(route('intranet.login'));
    }

    public function test_un_client_no_puede_usar_el_perfil_de_gestion(): void
    {
        $user = $this->usuario('client');

        $this->actingAs($user)
            ->put(route('manager.profile.update'), $this->datos())
            ->assertForbidden();

        $this->assertNotSame('Nombre Nuevo', $user->fresh()->name);
    }
}