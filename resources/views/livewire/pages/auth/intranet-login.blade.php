<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Enums\UserRole;

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

        if (! in_array($user->role, [UserRole::Manager, UserRole::Admin])) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Esta zona es solo para gestores y administradores.',
            ]);
        }

        request()->session()->regenerate();

        $this->redirect(route('manager.dashboard'), navigate: true);
    }
}; ?>

<div class="livewire-wrapper">
    <div class="card card-login border-0" style="max-width: 480px; width: 100%;">
        <div class="card-body p-4">
            <h1 class="h3 mb-4 text-center">Iniciar sesión en la intranet</h1>
            <hr>
            <form wire:submit="login" class="needs-validation" novalidate>
                {{-- Email --}}
                <div class="mb-3">
                    <label for="email" class="form-label">{{ __('Email') }}</label>
                    <input wire:model="email" id="email" type="email" name="email" placeholder="correo@ejemplo.com"
                        class="form-control @error('email') is-invalid @enderror"
                        required autofocus autocomplete="username">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Contraseña --}}
                <div class="mb-3">
                    <label for="password" class="form-label">{{ __('Contraseña') }}</label>
                    <input wire:model="password" id="password" type="password" name="password" placeholder="Contraseña"
                        class="form-control @error('password') is-invalid @enderror"
                        required autocomplete="current-password">
                    @error('password')
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