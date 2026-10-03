<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_paginas_de_invitado_cargan(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/forgot-password')->assertOk();
        $this->get('/reset-password/token-falso')->assertOk();
    }

    public function test_un_invitado_no_puede_ver_verify_email(): void
    {
        $this->get('/verify-email')->assertRedirect('/login');
    }

    public function test_un_usuario_no_verificado_es_enviado_a_verificar_su_email(): void
    {
        $user = User::factory()->client()->unverified()->create();

        $this->actingAs($user)
             ->get(route('client.dashboard'))
             ->assertRedirect(route('verification.notice'));
    }

    public function test_logout_cierra_la_sesion(): void
    {
        $user = User::factory()->client()->create();

        $this->actingAs($user)->post(route('logout'))->assertRedirect('/');

        $this->assertGuest();
    }
}