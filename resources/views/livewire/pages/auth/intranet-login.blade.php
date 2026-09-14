<?php

use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales son incorrectas.',
            ]);
        }

        $user = Auth::user();

        if (! in_array($user->role, ['manager', 'admin'])) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Esta zona es solo para gestores y administradores.',
            ]);
        }

        request()->session()->regenerate();

        $this->redirect(route('manager.dashboard'), navigate: true);
    }
}; ?>

<div class="container my-5" style="max-width: 480px;">
    <h1 class="h3 mb-4 text-center">Iniciar sesión en la intranet</h1>
    <form wire:submit="login">
        {{-- Email --}}
        <div class="mb-3">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input wire:model="email" id="email" type="email" name="email"
                   class="form-control @error('email') is-invalid @enderror"
                   required autofocus autocomplete="username">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Contraseña --}}
        <div class="mb-3">
            <label for="password" class="form-label">{{ __('Contraseña') }}</label>
            <input wire:model="password" id="password" type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   required autocomplete="current-password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- ¿Olvidaste tu contraseña? --}}
        
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