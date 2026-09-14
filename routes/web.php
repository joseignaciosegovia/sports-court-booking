<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Common\ProfileController;
use App\Http\Controllers\Client\ReservationController;
use App\Http\Controllers\Client\FeedbackController;
use App\Http\Controllers\Admin\ManagerController;
use App\Http\Controllers\Common\StripeWebhookController;
use App\Models\Feedback;
use Livewire\Volt\Volt;


// ─────────────────────────────────────────────
// ZONA PÚBLICA
// ─────────────────────────────────────────────

Route::get('/', [HomeController::class, 'index'])
    ->name('home');

Route::post('/crearUsuario', [HomeController::class, 'register'])
    ->name('register');

// Consulta pública de horarios (solo lectura, sin reservar)
Route::get('/horarios/{court}', [HomeController::class, 'schedule'])
    ->name('public.courts.schedule');

// Webhook de Stripe: pública y sin CSRF, porque la llama Stripe, no un usuario logueado
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])
    ->name('stripe.webhook')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Volt::route('/intranet/login', 'pages.auth.intranet-login')
    ->name('intranet.login')
    ->middleware('guest');

// ─────────────────────────────────────────────
// ZONA PRIVADA DEL CLIENT
// ─────────────────────────────────────────────

Route::middleware(['auth', 'verified', 'role:client'])->group(function () {

    // Inicio
    Route::get('/panelControl', [DashboardController::class, 'index'])
        ->name('client.dashboard');

    // Perfil
    Route::get('/perfil', [ProfileController::class, 'edit'])
        ->name('client.profile.edit');

    Route::put('/perfil', [ProfileController::class, 'update'])
        ->name('client.profile.update');

    // Reservas
    Route::get('/reservas', [ReservationController::class, 'create'])
        ->name('client.reservations.create');
    
    Route::get('/reservas/horarios/{court}', [ReservationController::class, 'schedule'])
        ->name('client.reservations.schedule');

    Route::post('/reservas', [ReservationController::class, 'store'])
        ->name('client.reservations.store');

    Route::patch('/reservas/{reservation}/cancelar', [ReservationController::class, 'cancel'])
        ->name('client.reservations.cancel');
    
    Route::get('/reservas/{reservation}/pago/exito', [ReservationController::class, 'paymentSuccess'])
        ->name('client.reservations.payment.success');

    Route::get('/reservas/{reservation}/pago/cancelado', [ReservationController::class, 'paymentCancel'])
        ->name('client.reservations.payment.cancel');

    // Historial de reservas
    Route::get('/reservas/historial', [ReservationController::class, 'index'])
        ->name('client.reservations.index');

    // Sugerencias e incidencias
    Route::get('/comentarios', [FeedbackController::class, 'index'])
        ->name('client.feedback.index');

    Route::post('/comentarios', [FeedbackController::class, 'store'])
        ->name('client.feedback.store');
});

// ─────────────────────────────────────────────
// ZONA PRIVADA DEL MANAGER/ADMIN
// ─────────────────────────────────────────────

Route::middleware(['auth', 'role:manager,admin'])
    ->prefix('gestion')
    ->name('manager.')
    ->group(function () {
        Route::resource('pistas', \App\Http\Controllers\Manager\CourtController::class)
            ->except(['show'])
            ->names('courts')
            ->parameters(['pistas' => 'court']);

        Route::get('panelControl', [\App\Http\Controllers\Manager\DashboardController::class, 'index'])
            ->name('dashboard');

        // Perfil
        Route::get('/perfil', [ProfileController::class, 'edit'])
            ->name('profile.edit');

        Route::put('/perfil', [ProfileController::class, 'update'])
            ->name('profile.update');

        // Reservas
        Route::get('pistas/{court}/calendario', [\App\Http\Controllers\Manager\ReservationController::class, 'calendar'])
            ->name('courts.calendar');

        Route::get('reservas/crear', [\App\Http\Controllers\Manager\ReservationController::class, 'create'])
            ->name('reservations.create');

        Route::post('reservas', [\App\Http\Controllers\Manager\ReservationController::class, 'store'])
            ->name('reservations.store');

        Route::get('pistas/{court}/eventos', [\App\Http\Controllers\Manager\ReservationController::class, 'events'])
            ->name('courts.events');

        Route::get('reservas/{reservation}/editar', [\App\Http\Controllers\Manager\ReservationController::class, 'edit'])
            ->name('reservations.edit');

        Route::put('reservas/{reservation}', [\App\Http\Controllers\Manager\ReservationController::class, 'update'])
            ->name('reservations.update');

        Route::get('reservas', [\App\Http\Controllers\Manager\ReservationController::class, 'index'])
            ->name('reservations.index');

        Route::get('reservas/{reservation}/informacion', [\App\Http\Controllers\Manager\ReservationController::class, 'show'])
            ->name('reservations.show');

        Route::patch('reservas/{reservation}/reprogramar', [\App\Http\Controllers\Manager\ReservationController::class, 'reschedule'])
            ->name('reservations.reschedule');

        Route::post('pistas/{court}/reservas/creacion-rapida', [\App\Http\Controllers\Manager\ReservationController::class, 'quickStore'])
            ->name('reservations.quick.store');

        Route::patch('reservas/{reservation}/cancelar', [\App\Http\Controllers\Manager\ReservationController::class, 'cancel'])
            ->name('reservations.cancel');

        Route::get('reservas/cancelaciones', [\App\Http\Controllers\Manager\ReservationController::class, 'cancellations'])
            ->name('reservations.cancellations');

        // Sugerencias
        Route::get('comentarios', [\App\Http\Controllers\Manager\FeedbackController::class, 'index'])
            ->name('feedback.index');
    });

// ─────────────────────────────────────────────
// ZONA PRIVADA DEL ADMIN
// ─────────────────────────────────────────────

Route::middleware(['auth', 'role:admin'])
    ->prefix('administrador')
    ->name('admin.')
    ->group(function () {
        // Perfil
        Route::get('/perfil', [ProfileController::class, 'edit'])
            ->name('profile.edit');

        Route::put('/perfil', [ProfileController::class, 'update'])
            ->name('profile.update');
        
        // Administrar gestores
        Route::resource('gestores', ManagerController::class)
            ->except(['show'])
            ->names('managers')
            ->parameters(['gestores' => 'manager']);
    });

require __DIR__.'/auth.php';