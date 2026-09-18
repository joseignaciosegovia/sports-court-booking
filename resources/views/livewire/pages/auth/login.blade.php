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


<div class="container my-5" style="max-width: 480px;">
    <h1 class="h3 mb-4 text-center">Iniciar sesión</h1>
    <p class="text-center">¿No tienes cuenta? <a href="{{ route('home') }}"><u>Regístrate aquí</u></a></p>
    
    {{-- Session Status --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />
    <hr>
    <form wire:submit="login">
        {{-- Email Address --}}
        <div class="mb-3">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input wire:model="form.email" id="email" type="email" name="email"
                   class="form-control @error('form.email') is-invalid @enderror"
                   required autofocus autocomplete="username">
            @error('form.email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Password --}}
        <div class="mb-3">
            <label for="password" class="form-label">{{ __('Contraseña') }}</label>
            <input wire:model="form.password" id="password" type="password" name="password"
                   class="form-control @error('form.password') is-invalid @enderror"
                   required autocomplete="current-password">
            @error('form.password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Recordarme --}}
        <div class="mb-3 form-check">
            <input wire:model="form.remember" id="remember" type="checkbox" name="remember" class="form-check-input">
            <label for="remember" class="form-check-label">{{ __('Recordar cuenta') }}</label>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            @if (Route::has('password.request'))
                <a class="small text-decoration-underline" href="{{ route('password.request') }}" wire:navigate>
                    {{ __('¿Olvidaste tu contraseña?') }}
                </a>
            @endif

            <button type="submit" class="btn btn-primary">
                {{ __('Iniciar sesión') }}
            </button>
        </div>
    </form>
</div>