<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\SendEmailVerificationNotificationController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Auth;
use App\Livewire\Actions\Logout;

// Usuario que no ha iniciado sesión
Route::middleware('guest')->group(function () {
    Volt::route('login', 'pages.auth.login')
        ->name('login');

    // Formulario para pedir el enlace de restablecimiento de contraseña
    Volt::route('forgot-password', 'pages.auth.forgot-password')
        ->name('password.request');

    // Formulario donde el usuario introduce la nueva contraseña, usando el {token} que se le envió por correo
    Volt::route('reset-password/{token}', 'pages.auth.reset-password')
        ->name('password.reset');
});

// Usuario que ha iniciado sesión
Route::middleware('auth')->group(function () {
    Volt::route('verify-email', 'pages.auth.verify-email')
        ->name('verification.notice');

    // Esta es la ruta a la que apunta el enlace del correo de verificación
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Volt::route('confirm-password', 'pages.auth.confirm-password')
        ->name('password.confirm');

    // Si el usuario pincha en el botón para cerrar sesión
    Route::post('logout', function (Logout $logout) {
        $logout();
        return redirect('/');
    })->name('logout');
});
