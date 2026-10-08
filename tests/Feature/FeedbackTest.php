<?php

namespace Tests\Feature;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    private function client(): User
    {
        return User::factory()->client()->create();
    }

    private function datos(array $extra = []): array
    {
        return [
            'type'    => 'suggestion',
            'content' => 'Sería genial tener más focos en la pista cuatro',
            ...$extra,
        ];
    }

    // ───── Cliente: crear ─────

    public function test_el_client_ve_el_formulario_de_comentarios(): void
    {
        $this->actingAs($this->client())
            ->get(route('client.feedback.create'))
            ->assertOk();
    }

    public function test_el_client_envia_un_comentario(): void
    {
        $client = $this->client();

        $this->actingAs($client)
            ->post(route('client.feedback.store'), $this->datos())
            ->assertRedirect(route('client.feedback.index'))
            ->assertSessionHas('success', 'Comentario enviado correctamente.');

        $this->assertDatabaseHas('feedback', [
            'user_id' => $client->id,
            'type'    => 'suggestion',
            'content' => 'Sería genial tener más focos en la pista cuatro',
        ]);
    }

    public function test_se_puede_enviar_una_incidencia(): void
    {
        $this->actingAs($this->client())
            ->post(route('client.feedback.store'), $this->datos(['type' => 'incident']))
            ->assertRedirect(route('client.feedback.index'));

        $this->assertDatabaseHas('feedback', ['type' => 'incident']);
    }

    public function test_el_comentario_exige_tipo_y_contenido(): void
    {
        $this->actingAs($this->client())
            ->post(route('client.feedback.store'), [])
            ->assertSessionHasErrors(['type', 'content']);

        $this->assertDatabaseCount('feedback', 0);
    }

    public function test_el_tipo_debe_ser_uno_de_los_permitidos(): void
    {
        $this->actingAs($this->client())
            ->post(route('client.feedback.store'), $this->datos(['type' => 'queja-inventada']))
            ->assertSessionHasErrors('type'); 

        $this->assertDatabaseCount('feedback', 0);
    }

    public function test_no_se_puede_comentar_en_nombre_de_otro_usuario(): void
    {
        $client = $this->client();
        $otro   = $this->client();

        $this->actingAs($client)
            ->post(route('client.feedback.store'), $this->datos(['user_id' => $otro->id]));

        $this->assertDatabaseHas('feedback', ['user_id' => $client->id]);
        $this->assertDatabaseMissing('feedback', ['user_id' => $otro->id]);
    }

    // ───── Cliente: listado ─────

    public function test_el_client_solo_ve_sus_propios_comentarios(): void
    {
        $client = $this->client();
        Feedback::factory()->create(['user_id' => $client->id, 'content' => 'Comentario propio visible']);
        Feedback::factory()->create(['content' => 'Comentario ajeno oculto']);

        $this->actingAs($client)
            ->get(route('client.feedback.index'))
            ->assertOk()
            ->assertSee('Comentario propio visible')
            ->assertDontSee('Comentario ajeno oculto');
    }

    public function test_el_client_filtra_por_tipo(): void
    {
        $client = $this->client();
        Feedback::factory()->create(['user_id' => $client->id, 'content' => 'Texto de sugerencia']);
        Feedback::factory()->incident()->create(['user_id' => $client->id, 'content' => 'Texto de incidencia']);

        $this->actingAs($client)
            ->get(route('client.feedback.index', ['type' => 'incident']))
            ->assertOk()
            ->assertSee('Texto de incidencia')
            ->assertDontSee('Texto de sugerencia');
    }

    public function test_el_client_filtra_por_fecha(): void
    {
        $client = $this->client();
        Feedback::factory()->create([
            'user_id'    => $client->id,
            'content'    => 'Comentario de ayer',
            'created_at' => now()->subDay(),
        ]);
        Feedback::factory()->create([
            'user_id' => $client->id,
            'content' => 'Comentario de hoy',
        ]);

        $this->actingAs($client)
            ->get(route('client.feedback.index', ['date' => now()->format('Y-m-d')]))
            ->assertOk()
            ->assertSee('Comentario de hoy')
            ->assertDontSee('Comentario de ayer');
    }

    public function test_el_listado_se_pagina_de_10_en_10(): void
    {
        $client = $this->client();
        Feedback::factory()->count(12)->create(['user_id' => $client->id]);

        $this->actingAs($client)
            ->get(route('client.feedback.index'))
            ->assertOk()
            ->assertViewHas('feedback', fn ($p) => $p->count() === 10 && $p->total() === 12);
    }

    // ───── Manager ─────

    public function test_el_manager_ve_los_comentarios_de_todos_los_clientes(): void
    {
        Feedback::factory()->create(['content' => 'Comentario de la cliente uno']);
        Feedback::factory()->incident()->create(['content' => 'Incidencia de la cliente dos']);

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('manager.feedback.index'))
            ->assertOk()
            ->assertSee('Comentario de la cliente uno')
            ->assertSee('Incidencia de la cliente dos');
    }

    public function test_un_admin_tambien_ve_los_comentarios(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('manager.feedback.index'))
            ->assertOk();
    }

    public function test_el_manager_filtra_por_tipo(): void
    {
        Feedback::factory()->create(['content' => 'Texto de sugerencia']);
        Feedback::factory()->incident()->create(['content' => 'Texto de incidencia']);

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('manager.feedback.index', ['type' => 'suggestion']))
            ->assertOk()
            ->assertSee('Texto de sugerencia')
            ->assertDontSee('Texto de incidencia');
    }

    public function test_el_listado_del_manager_no_falla_si_el_autor_fue_borrado(): void
    {
        $autor = User::factory()->client()->create(['name' => 'Cliente De Baja']);
        Feedback::factory()->create(['user_id' => $autor->id, 'content' => 'Comentario de baja']);
        $autor->delete();

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('manager.feedback.index'))
            ->assertOk()
            ->assertSee('Comentario de baja')
            ->assertSee('Cliente De Baja');
    }

    // ───── Permisos ─────

    public function test_un_client_no_ve_el_listado_del_manager(): void
    {
        $this->actingAs($this->client())
            ->get(route('manager.feedback.index'))
            ->assertForbidden();
    }

    public function test_un_manager_no_usa_la_zona_de_comentarios_del_client(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->get(route('client.feedback.create'))->assertForbidden();
        $this->actingAs($manager)->post(route('client.feedback.store'), $this->datos())->assertForbidden();

        $this->assertDatabaseCount('feedback', 0);
    }

    public function test_un_invitado_es_redirigido_al_login(): void
    {
        $this->post(route('client.feedback.store'), $this->datos())->assertRedirect(route('login'));
        $this->get(route('manager.feedback.index'))->assertRedirect(route('intranet.login'));

        $this->assertDatabaseCount('feedback', 0);
    }

    public function test_un_client_sin_verificar_no_puede_comentar(): void
    {
        $this->actingAs(User::factory()->client()->unverified()->create())
            ->post(route('client.feedback.store'), $this->datos())
            ->assertRedirect(route('verification.notice'));

        $this->assertDatabaseCount('feedback', 0);
    }

    public function test_el_contenido_admite_255_caracteres_pero_no_256(): void
    {
        $client = $this->client();

        $this->actingAs($client)
            ->post(route('client.feedback.store'), $this->datos(['content' => str_repeat('a', 255)]))
            ->assertRedirect(route('client.feedback.index'));

        $this->assertDatabaseCount('feedback', 1);

        $this->actingAs($client)
            ->post(route('client.feedback.store'), $this->datos(['content' => str_repeat('a', 256)]))
            ->assertSessionHasErrors('content');

        $this->assertDatabaseCount('feedback', 1);
    }

    public function test_el_contenido_no_puede_ser_un_array(): void
    {
        $this->actingAs($this->client())
            ->post(route('client.feedback.store'), $this->datos(['content' => ['x']]))
            ->assertSessionHasErrors('content');
    }
}