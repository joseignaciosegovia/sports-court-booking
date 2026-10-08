<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminManagerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function datos(array $extra = []): array
    {
        return [
            'name'                  => 'Laura Gestora',
            'email'                 => 'laura@example.com',
            'password'              => 'secreto123',
            'password_confirmation' => 'secreto123',
            'dni'                   => '12345678Z',
            'phone'                 => '600123456',
            'role'                  => 'manager',
            ...$extra,
        ];
    }

    // ───── Permisos ─────

    public function test_un_manager_y_un_client_no_pueden_gestionar_gestores(): void
    {
        $objetivo = User::factory()->manager()->create();

        foreach ([User::factory()->manager()->create(), User::factory()->client()->create()] as $usuario) {
            $total = User::count();

            $this->actingAs($usuario)->post(route('admin.managers.store'), $this->datos())->assertForbidden();
            $this->actingAs($usuario)->put(route('admin.managers.update', $objetivo), $this->datos())->assertForbidden();
            $this->actingAs($usuario)->delete(route('admin.managers.destroy', $objetivo))->assertForbidden();

            $this->assertSame($total, User::count());
            $this->assertNotSoftDeleted('users', ['id' => $objetivo->id]);
        }
    }

    public function test_un_invitado_va_al_login_de_intranet(): void
    {
        $objetivo = User::factory()->manager()->create();

        $this->delete(route('admin.managers.destroy', $objetivo))
            ->assertRedirect(route('intranet.login'));

        $this->assertNotSoftDeleted('users', ['id' => $objetivo->id]);
    }

    // ───── Listado ─────

    public function test_el_listado_muestra_gestores_y_admins_pero_no_clientes(): void
    {
        User::factory()->manager()->create(['name' => 'Gestora Visible']);
        User::factory()->client()->create(['name' => 'Cliente Oculto']);

        $this->actingAs($this->admin())
            ->get(route('admin.managers.index'))
            ->assertOk()
            ->assertSee('Gestora Visible')
            ->assertDontSee('Cliente Oculto');
    }

    // ───── Crear ─────

    public function test_el_admin_crea_un_gestor_con_la_contrasena_hasheada(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.managers.store'), $this->datos())
            ->assertRedirect(route('admin.managers.index'))
            ->assertSessionHas('success', 'Gestor creado correctamente.');

        $nuevo = User::where('email', 'laura@example.com')->firstOrFail();

        $this->assertSame('manager', $nuevo->role->value);
        $this->assertNotSame('secreto123', $nuevo->password);
        // Si el servicio hashea y el cast 'hashed' vuelve a hashear, esto falla
        $this->assertTrue(Hash::check('secreto123', $nuevo->password));
    }

    public function test_el_admin_puede_crear_otro_admin(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.managers.store'), $this->datos(['role' => 'admin']))
            ->assertRedirect(route('admin.managers.index'));

        $this->assertSame('admin', User::where('email', 'laura@example.com')->firstOrFail()->role->value);
    }

    public function test_no_se_puede_crear_un_usuario_con_rol_client_ni_inventado(): void
    {
        $admin = $this->admin();

        foreach (['client', 'superadmin'] as $rol) {
            $this->actingAs($admin)
                ->post(route('admin.managers.store'), $this->datos(['role' => $rol]))
                ->assertSessionHasErrors('role');
        }

        $this->assertDatabaseCount('users', 1);
    }

    public function test_crear_exige_los_campos_obligatorios(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.managers.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'password', 'dni', 'role']);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_la_contrasena_debe_confirmarse_y_tener_8_caracteres(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.managers.store'), $this->datos(['password_confirmation' => 'otra']))
            ->assertSessionHasErrors('password');

        $this->actingAs($admin)
            ->post(route('admin.managers.store'), $this->datos(['password' => '1234567', 'password_confirmation' => '1234567']))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_no_se_repite_el_email(): void
    {
        User::factory()->client()->create(['email' => 'laura@example.com']);

        $this->actingAs($this->admin())
            ->post(route('admin.managers.store'), $this->datos())
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 2);
    }

    public function test_el_email_de_un_gestor_borrado_da_error_de_validacion_y_no_un_500(): void
    {
        $viejo = User::factory()->manager()->create(['email' => 'laura@example.com']);
        $viejo->delete();

        $this->actingAs($this->admin())
            ->post(route('admin.managers.store'), $this->datos())
            ->assertSessionHasErrors('email');
    }

    public function test_no_se_repite_el_dni(): void
    {
        User::factory()->client()->create(['dni' => '12345678Z']);

        $this->actingAs($this->admin())
            ->post(route('admin.managers.store'), $this->datos())
            ->assertSessionHasErrors('dni');   // si no hay regla unique, falla (500 o duplicado)

        $this->assertDatabaseCount('users', 2);
    }

    public function test_la_foto_debe_ser_una_imagen(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.managers.store'), $this->datos([
                'photo' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('photo');
    }

    public function test_se_guarda_la_foto_del_gestor(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('admin.managers.store'), $this->datos([
                'photo' => UploadedFile::fake()->image('foto.jpg'),
            ]))
            ->assertRedirect(route('admin.managers.index'));

        $nuevo = User::where('email', 'laura@example.com')->firstOrFail();

        $this->assertNotNull($nuevo->photo);
        Storage::disk('public')->assertExists($nuevo->photo);
    }

    // ───── Editar ─────

    public function test_el_admin_ve_el_formulario_de_edicion(): void
    {
        $gestor = User::factory()->manager()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.managers.edit', $gestor))
            ->assertOk();
    }

    public function test_el_admin_actualiza_un_gestor_sin_chocar_con_su_propio_email(): void
    {
        $gestor = User::factory()->manager()->create(['email' => 'laura@example.com']);

        $this->actingAs($this->admin())
            ->put(route('admin.managers.update', $gestor), $this->datos([
                'name' => 'Laura Renombrada',
                'password' => null,
                'password_confirmation' => null,
            ]))
            ->assertRedirect(route('admin.managers.index'))
            ->assertSessionHas('success', 'Gestor actualizado correctamente.');

        $this->assertSame('Laura Renombrada', $gestor->fresh()->name);
    }

    public function test_no_se_puede_usar_el_email_de_otro_usuario(): void
    {
        User::factory()->client()->create(['email' => 'ocupado@example.com']);
        $gestor = User::factory()->manager()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.managers.update', $gestor), $this->datos(['email' => 'ocupado@example.com']))
            ->assertSessionHasErrors('email');
    }

    public function test_dejar_la_contrasena_vacia_no_la_cambia(): void
    {
        $gestor = User::factory()->manager()->create();   // contraseña: "password"

        $this->actingAs($this->admin())
            ->put(route('admin.managers.update', $gestor), $this->datos([
                'email' => $gestor->email,
                'password' => null,
                'password_confirmation' => null,
            ]))
            ->assertRedirect(route('admin.managers.index'));

        $this->assertTrue(Hash::check('password', $gestor->fresh()->password));
    }

    public function test_se_puede_cambiar_la_contrasena(): void
    {
        $gestor = User::factory()->manager()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.managers.update', $gestor), $this->datos([
                'email' => $gestor->email,
                'password' => 'nueva-clave-1',
                'password_confirmation' => 'nueva-clave-1',
            ]))
            ->assertRedirect(route('admin.managers.index'));

        $this->assertTrue(Hash::check('nueva-clave-1', $gestor->fresh()->password));
    }

    public function test_no_se_puede_degradar_un_gestor_a_client(): void
    {
        $gestor = User::factory()->manager()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.managers.update', $gestor), $this->datos(['email' => $gestor->email, 'role' => 'client']))
            ->assertSessionHasErrors('role');

        $this->assertSame('manager', $gestor->fresh()->role->value);
    }

    // ───── Alcance: esta zona solo debe tocar gestores y admins ─────

    public function test_esta_zona_no_puede_editar_ni_borrar_clientes(): void
    {
        $admin  = $this->admin();
        $client = User::factory()->client()->create();

        $this->actingAs($admin)->get(route('admin.managers.edit', $client))->assertNotFound();

        $this->actingAs($admin)
            ->put(route('admin.managers.update', $client), $this->datos(['email' => $client->email, 'role' => 'manager']))
            ->assertNotFound();

        $this->actingAs($admin)->delete(route('admin.managers.destroy', $client))->assertNotFound();

        $client->refresh();
        $this->assertSame('client', $client->role->value);
        $this->assertNotSoftDeleted('users', ['id' => $client->id]);
    }

    // ───── Borrar ─────

    public function test_el_admin_borra_un_gestor(): void
    {
        $gestor = User::factory()->manager()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.managers.destroy', $gestor))
            ->assertRedirect(route('admin.managers.index'))
            ->assertSessionHas('success', 'Gestor eliminado correctamente.');

        $this->assertSoftDeleted('users', ['id' => $gestor->id]);
    }

    public function test_un_admin_puede_borrar_a_otro_admin(): void
    {
        $otro = User::factory()->admin()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.managers.destroy', $otro))
            ->assertRedirect(route('admin.managers.index'));

        $this->assertSoftDeleted('users', ['id' => $otro->id]);
    }

    public function test_un_admin_no_puede_borrarse_a_si_mismo(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.managers.index'))
            ->delete(route('admin.managers.destroy', $admin))
            ->assertRedirect(route('admin.managers.index'))
            ->assertSessionHasErrors('manager');

        $this->assertNotSoftDeleted('users', ['id' => $admin->id]);
    }

    public function test_un_admin_no_puede_quitarse_a_si_mismo_el_rol_de_admin(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.managers.edit', $admin))
            ->put(route('admin.managers.update', $admin), $this->datos(['email' => $admin->email, 'role' => 'manager']))
            ->assertSessionHasErrors('role');

        $this->assertSame('admin', $admin->fresh()->role->value);
    }

    public function test_al_cambiar_la_foto_se_borra_la_anterior(): void
    {
        Storage::fake('public');
        $gestor = User::factory()->manager()->create();
        $gestor->photo = UploadedFile::fake()->image('vieja.jpg')->store('profiles', 'public');
        $gestor->save();
        $rutaVieja = $gestor->photo;

        $this->actingAs($this->admin())
            ->put(route('admin.managers.update', $gestor), $this->datos([
                'email'                 => $gestor->email,
                'password'              => null,
                'password_confirmation' => null,
                'photo'                 => UploadedFile::fake()->image('nueva.jpg'),
            ]))
            ->assertRedirect(route('admin.managers.index'));

        $gestor->refresh();
        $this->assertNotSame($rutaVieja, $gestor->photo);
        Storage::disk('public')->assertMissing($rutaVieja);
        Storage::disk('public')->assertExists($gestor->photo);
    }
}