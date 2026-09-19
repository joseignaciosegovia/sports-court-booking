<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Enums\UserRole;

new #[Layout('layouts.app')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $user = Auth::user();

        $target = match ($user->role) {
            UserRole::Client => route('client.dashboard'),
            UserRole::Manager => route('manager.dashboard'),
            UserRole::Admin => route('manager.dashboard'),
            default => route('home'),
        };

        $this->redirect($target, navigate: true);
    }
}; ?>


<div class="livewire-wrapper">
    <div class="card card-login border-0" style="max-width: 480px; width: 100%;">
        <div class="card-body p-4">
            <h1 class="h3 mb-1 text-center">Iniciar sesión</h1>
            <p class="text-center">¿No tienes cuenta? <a href="{{ route('home') }}"><u>Regístrate aquí</u></a></p>
            
            {{-- Session Status --}}
            <x-auth-session-status class="mb-4" :status="session('status')" />
            <hr>
            <form wire:submit="login" class="needs-validation" novalidate>
                {{-- Email Address --}}
                <div class="mb-3">
                    <label for="email" class="form-label">{{ __('Email') }}</label>
                    <input wire:model="form.email" id="email" type="email" name="email" placeholder="correo@ejemplo.com"
                        class="form-control @error('form.email') is-invalid @enderror"
                        required autofocus autocomplete="username">
                    @error('form.email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="mb-3">
                    <label for="password" class="form-label">{{ __('Contraseña') }}</label>
                    <input wire:model="form.password" id="password" type="password" name="password" placeholder="Contraseña"
                        class="form-control @error('form.password') is-invalid @enderror"
                        required autocomplete="current-password">
                    @error('form.password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Recordarme y "Olvidaste tu contraseña" --}}
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input wire:model="form.remember" id="remember" type="checkbox" name="remember" class="form-check-input">
                        <label for="remember" class="form-check-label">{{ __('Recordar cuenta') }}</label>
                    </div>
                    @if (Route::has('password.request'))
                        <a class="small text-decoration-underline" href="{{ route('password.request') }}" wire:navigate>
                            {{ __('¿Olvidaste tu contraseña?') }}
                        </a>
                    @endif
                </div>
                {{-- Botón de inicio de sesión --}}
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        {{ __('Iniciar sesión') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    @vite('resources/js/validation.js')
@endpush