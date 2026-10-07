<?php

namespace Tests\Feature;

use App\Services\Common\StripeWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Event;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.stripe.webhook_secret' => $this->secret]);
    }

    private function enviar(array $evento, ?string $secreto = null)
    {
        $payload   = json_encode($evento);
        $timestamp = time();
        $firma     = hash_hmac('sha256', "{$timestamp}.{$payload}", $secreto ?? $this->secret);

        return $this->call('POST', route('stripe.webhook'), [], [], [], [
            'CONTENT_TYPE'          => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$firma}",
        ], $payload);
    }

    private function evento(): array
    {
        return [
            'id'     => 'evt_test_1',
            'object' => 'event',
            'type'   => 'checkout.session.completed',
            'data'   => ['object' => ['id' => 'cs_test_123', 'object' => 'checkout.session']],
        ];
    }

    public function test_una_firma_invalida_devuelve_400_y_no_procesa_nada(): void
    {
        $this->mock(StripeWebhookService::class)->shouldNotReceive('handleEvent');

        $this->enviar($this->evento(), 'secreto-equivocado')
            ->assertStatus(400);
    }

    public function test_sin_cabecera_de_firma_devuelve_400(): void
    {
        $this->mock(StripeWebhookService::class)->shouldNotReceive('handleEvent');

        $this->postJson(route('stripe.webhook'), $this->evento())
            ->assertStatus(400);
    }

    public function test_una_firma_valida_se_entrega_al_servicio(): void
    {
        $this->mock(StripeWebhookService::class, function ($mock) {
            $mock->shouldReceive('handleEvent')
                ->once()
                ->withArgs(fn (Event $e) => $e->id === 'evt_test_1'
                    && $e->type === 'checkout.session.completed');
        });

        $this->enviar($this->evento())
            ->assertOk()
            ->assertSee('OK');
    }
}