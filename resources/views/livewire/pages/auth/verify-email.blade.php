<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?string $verificationUrl = null;

    public function mount(): void
    {
        $this->generateVerificationUrl();
    }

    public function generateVerificationUrl(): void
    {
        $user = Auth::user();

        if (!$user || $user->hasVerifiedEmail()) {
            return;
        }

        $this->verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        Session::flash('status', 'verification-link-generated');
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div class="livewire-wrapper">
    <div class="card card-login border-0" style="max-width: 550px; width: 100%;">
        <div class="card-body p-4">
            <h1 class="h3 mb-4 text-center">Verifique su email</h1>

            <div class="mb-4 text-muted small">
                Para esta demostración, no enviamos correos electrónicos.
                Utilice el siguiente enlace para verificar su dirección
                de correo y activar su cuenta.
            </div>

            @if ($verificationUrl)
                <div class="alert alert-info">
                    <p class="mb-2">
                        <strong>Enlace de verificación:</strong>
                    </p>

                    <a href="{{ $verificationUrl }}"
                       class="text-break"
                       style="overflow-wrap: anywhere;">
                        {{ $verificationUrl }}
                    </a>

                    <div class="mt-3">
                        <button
                            type="button"
                            class="btn btn-outline-primary btn-sm"
                            onclick="navigator.clipboard.writeText(@js($verificationUrl))">
                            Copiar enlace
                        </button>
                    </div>

                    <p class="small mt-3 mb-0">
                        El enlace caduca en 60 minutos.
                    </p>
                </div>
            @else
                <div class="alert alert-success">
                    Tu cuenta ya está verificada.
                </div>
            @endif

            <div class="d-flex align-items-center justify-content-between mt-4">
                @if ($verificationUrl)
                    <button
                        wire:click="generateVerificationUrl"
                        type="button"
                        class="btn btn-primary">
                        Generar otro enlace
                    </button>
                @endif

                <button
                    wire:click="logout"
                    type="button"
                    class="btn btn-link text-decoration-underline text-muted">
                    Cerrar sesión
                </button>
            </div>
        </div>
    </div>
</div>