<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Services\Common\StripeWebhookService;
use Illuminate\Http\Request;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request, StripeWebhookService $service)
    {
        error_log('========== STRIPE WEBHOOK CONTROLLER ==========');
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('services.stripe.webhook_secret')
            );
            error_log('STRIPE EVENT TYPE: ' . $event->type);
        } catch (SignatureVerificationException $e) {
            return response('Firma inválida', 400);
        }

        $service->handleEvent($event);
        error_log('STRIPE WEBHOOK SERVICE TERMINADO');

        return response('OK', 200);
    }
}