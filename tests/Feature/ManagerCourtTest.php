<?php

namespace Tests\Feature;

use App\Models\Court;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerCourtTest extends TestCase
{
    use RefreshDatabase;

    private function manager(): User
    {
        return User::factory()->manager()->create();
    }

    private function datos(array $extra = []): array
    {
        return [
            'name'              => 'Pista Central',
            'location'          => 'Polideportivo Norte',
            'reservation_price' => '12.50',
            ...$extra,
        ];
    }

    // ───── Crear ─────

    public function test_el_manager_crea_una_pista(): void
    {
        $this->actingAs($this->manager())
            ->post(route('manager.courts.store'), $this->datos())
            ->assertRedirect(route('manager.courts.index'))
            ->assertSessionHas('success', 'Pista creada correctamente.');

        $this->assertDatabaseHas('courts', [
            'name'              => 'Pista Central',
            'location'          => 'Polideportivo Norte',
            'reservation_price' => '12.50',
        ]);
    }

    public function test_crear_exige_los_campos_obligatorios(): void
    {
        $this->actingAs($this->manager())
            ->post(route('manager.courts.store'), [])
            ->assertSessionHasErrors(['name', 'location', 'reservation_price']);

        $this->assertDatabaseCount('courts', 0);
    }

    public function test_el_precio_debe_ser_numerico_y_no_negativo(): void
    {
        $manager = $this->manager();

        foreach (['abc', '-5'] as $precio) {
            $this->actingAs($manager)
                ->post(route('manager.courts.store'), $this->datos(['reservation_price' => $precio]))
                ->assertSessionHasErrors('reservation_price');
        }

        $this->assertDatabaseCount('courts', 0);
    }

    public function test_no_se_permite_repetir_nombre_y_localizacion(): void
    {
        Court::factory()->create(['name' => 'Pista Central', 'location' => 'Polideportivo Norte']);

        $this->actingAs($this->manager())
            ->post(route('manager.courts.store'), $this->datos())
            ->assertSessionHasErrors();   // debe ser un error de validación, no un 500

        $this->assertDatabaseCount('courts', 1);
    }

    public function test_el_mismo_nombre_en_otra_localizacion_es_valido(): void
    {
        Court::factory()->create(['name' => 'Pista Central', 'location' => 'Polideportivo Sur']);

        $this->actingAs($this->manager())
            ->post(route('manager.courts.store'), $this->datos())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('courts', 2);
    }

    public function test_se_puede_reutilizar_el_nombre_de_una_pista_borrada(): void
    {
        $manager = $this->manager();
        $vieja = Court::factory()->create(['name' => 'Pista Central', 'location' => 'Polideportivo Norte']);

        $this->actingAs($manager)
            ->delete(route('manager.courts.destroy', $vieja))
            ->assertRedirect(route('manager.courts.index'));

        $this->actingAs($manager)
            ->post(route('manager.courts.store'), $this->datos())
            ->assertRedirect(route('manager.courts.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('courts', 1);
        $this->assertDatabaseHas('courts', ['name' => 'Pista Central', 'deleted_at' => null]);
    }

    // ───── Editar ─────

    public function test_el_manager_ve_el_formulario_de_edicion(): void
    {
        $court = Court::factory()->create();

        $this->actingAs($this->manager())
            ->get(route('manager.courts.edit', $court))
            ->assertOk();
    }

    public function test_el_manager_actualiza_una_pista(): void
    {
        $court = Court::factory()->create();

        $this->actingAs($this->manager())
            ->put(route('manager.courts.update', $court), $this->datos(['name' => 'Pista Nueva']))
            ->assertRedirect(route('manager.courts.index'))
            ->assertSessionHas('success', 'Pista actualizada correctamente.');

        $this->assertSame('Pista Nueva', $court->fresh()->name);
    }

    public function test_actualizar_una_pista_sin_cambiar_nombre_ni_localizacion_no_choca_consigo_misma(): void
    {
        $court = Court::factory()->create(['name' => 'Pista Central', 'location' => 'Polideportivo Norte']);

        $this->actingAs($this->manager())
            ->put(route('manager.courts.update', $court), $this->datos(['reservation_price' => '20.00']))
            ->assertSessionHasNoErrors();

        $this->assertSame('20.00', $court->fresh()->reservation_price);
    }

    public function test_no_se_puede_renombrar_a_una_combinacion_existente(): void
    {
        Court::factory()->create(['name' => 'Pista Central', 'location' => 'Polideportivo Norte']);
        $otra = Court::factory()->create(['name' => 'Pista 2', 'location' => 'Polideportivo Norte']);

        $this->actingAs($this->manager())
            ->put(route('manager.courts.update', $otra), $this->datos())
            ->assertSessionHasErrors();

        $this->assertSame('Pista 2', $otra->fresh()->name);
    }

    // ───── Borrar ─────

    public function test_se_borra_una_pista_sin_reservas(): void
    {
        $court = Court::factory()->create();

        $this->actingAs($this->manager())
            ->delete(route('manager.courts.destroy', $court))
            ->assertRedirect(route('manager.courts.index'))
            ->assertSessionHas('success', 'Pista eliminada correctamente.');

        $this->assertDatabaseMissing('courts', ['id' => $court->id]);
    }

    public function test_no_se_borra_una_pista_con_reservas_activas(): void
    {
        $court = Court::factory()->create();
        Reservation::factory()->paid()->create(['court_id' => $court->id]);

        $this->actingAs($this->manager())
            ->from(route('manager.courts.index'))
            ->delete(route('manager.courts.destroy', $court))
            ->assertRedirect(route('manager.courts.index'))
            ->assertSessionHasErrors('court');

        $this->assertNotSoftDeleted('courts', ['id' => $court->id]);
    }

    public function test_una_pista_con_solo_reservas_canceladas_tampoco_se_puede_borrar(): void
    {
        // Documenta el comportamiento actual: exists() cuenta TODAS las reservas.
        // Si decides permitirlo, invierte este test.
        $court = Court::factory()->create();
        Reservation::factory()->canceled()->create(['court_id' => $court->id]);

        $this->actingAs($this->manager())
            ->delete(route('manager.courts.destroy', $court))
            ->assertSessionHasErrors('court');

        $this->assertNotSoftDeleted('courts', ['id' => $court->id]);
    }

    // ───── Lectura y permisos ─────

    public function test_el_listado_filtra_por_localizacion(): void
    {
        Court::factory()->create(['name' => 'Norte 1', 'location' => 'Polideportivo Norte']);
        Court::factory()->create(['name' => 'Sur 1', 'location' => 'Polideportivo Sur']);

        $this->actingAs($this->manager())
            ->get(route('manager.courts.index', ['location' => 'Polideportivo Norte']))
            ->assertOk()
            ->assertSee('Norte 1')
            ->assertDontSee('Sur 1');
    }

    public function test_un_client_no_puede_modificar_pistas(): void
    {
        $client = User::factory()->client()->create();
        $court  = Court::factory()->create();

        $this->actingAs($client)->post(route('manager.courts.store'), $this->datos())->assertForbidden();
        $this->actingAs($client)->put(route('manager.courts.update', $court), $this->datos())->assertForbidden();
        $this->actingAs($client)->delete(route('manager.courts.destroy', $court))->assertForbidden();

        $this->assertDatabaseCount('courts', 1);
        $this->assertNotSoftDeleted('courts', ['id' => $court->id]);
    }

    public function test_un_invitado_va_al_login_de_intranet(): void
    {
        $court = Court::factory()->create();

        $this->delete(route('manager.courts.destroy', $court))
            ->assertRedirect(route('intranet.login'));

        $this->assertNotSoftDeleted('courts', ['id' => $court->id]);
    }
}