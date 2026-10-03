<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    /** Rutas sin parámetros, agrupadas por zona */
    private const CLIENT  = ['client.dashboard', 'client.profile.edit', 'client.reservations.create',
                             'client.reservations.index', 'client.feedback.index', 'client.feedback.create'];
    private const MANAGER = ['manager.dashboard', 'manager.profile.edit', 'manager.courts.index',
                             'manager.courts.create', 'manager.reservations.index',
                             'manager.reservations.create', 'manager.reservations.cancellations',
                             'manager.feedback.index'];
    private const ADMIN   = ['admin.profile.edit', 'admin.managers.index', 'admin.managers.create'];

    private function crear(string $rol): User
    {
        return User::factory()->{$rol}()->create();
    }

    public function test_invitado_en_zona_client_va_al_login_normal(): void
    {
        foreach (self::CLIENT as $ruta) {
            $this->get(route($ruta))->assertRedirect(route('login'));
        }
    }

    public function test_diagnostico_redirect_de_invitado_en_manager_y_admin(): void
{
    $resultado = [];

    foreach ([...self::MANAGER, ...self::ADMIN] as $ruta) {
        $response = $this->get(route($ruta));
        $resultado[$ruta] = $response->headers->get('Location');
    }

    // Imprime la tabla completa ruta => destino
    fwrite(STDERR, print_r($resultado, true));

    foreach ($resultado as $ruta => $destino) {
        $this->assertSame(
            route('intranet.login'),
            $destino,
            "La ruta {$ruta} redirige a {$destino}"
        );
    }
}

    public function test_client_accede_a_su_zona_y_no_a_las_demas(): void
    {
        $client = $this->crear('client');

        foreach (self::CLIENT as $ruta) {
            $this->actingAs($client)->get(route($ruta))->assertOk();
        }
        foreach ([...self::MANAGER, ...self::ADMIN] as $ruta) {
            $this->actingAs($client)->get(route($ruta))->assertForbidden();
        }
    }

    public function test_manager_accede_a_gestion_pero_no_a_admin_ni_a_client(): void
    {
        $manager = $this->crear('manager');

        foreach (self::MANAGER as $ruta) {
            $this->actingAs($manager)->get(route($ruta))->assertOk();
        }
        foreach ([...self::CLIENT, ...self::ADMIN] as $ruta) {
            $this->actingAs($manager)->get(route($ruta))->assertForbidden();
        }
    }

    public function test_admin_accede_a_gestion_y_a_administracion(): void
    {
        $admin = $this->crear('admin');

        foreach ([...self::MANAGER, ...self::ADMIN] as $ruta) {
            $this->actingAs($admin)->get(route($ruta))->assertOk();
        }
        foreach (self::CLIENT as $ruta) {
            $this->actingAs($admin)->get(route($ruta))->assertForbidden();
        }
    }
}