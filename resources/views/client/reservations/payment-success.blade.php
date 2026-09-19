@extends('layouts.client')

@section('title', 'Reserva confirmada · Moral de Calatrava')

@section('titleHeader', 'Reservas · Moral de Calatrava')

@section('client-content')
    <main class="main">
        {{-- BIENVENIDA --}}
        <div class="welcome-bar">
            <div class="welcome-avatar">{{ $authUser->initials }}</div>
            <div class="welcome-text">
                <h1>Bienvenida/o, {{ $authUser->name }}</h1>
                <p>Hoy es {{ $todayLong }}</p>
            </div>
            <span class="badge badge-green">
                <i class="ti ti-circle-check" aria-hidden="true"></i> Sesión activa
            </span>
        </div>
        {{-- Reserva confirmada --}}
        <div class="card shadow-sm border-0">
            <div class="p-3 py-4">
                <div class="seccionSubtitulo">
                    <i class="ti ti-circle-check" aria-hidden="true"></i>
                    <div>
                        <h2>Reserva confirmada</h2>
                        <small class="text-muted">Gracias por tu reserva</small>
                    </div>
                </div>
                <hr class="mt-0 mb-4" style="border-color: #dee2e6;">
                @if ($reservation->payment_status === App\Enums\PaymentStatus::Paid)
                    <div class="alert alert-success d-flex align-items-center gap-2">
                        <i class="ti ti-circle-check" aria-hidden="true"></i>
                        <span>Tu pago se ha procesado correctamente y la reserva está confirmada.</span>
                    </div>
                @else
                    <div class="alert alert-warning d-flex align-items-center gap-2">
                        <i class="ti ti-clock" aria-hidden="true"></i>
                        <span>Estamos confirmando tu pago. Esto puede tardar unos segundos — recarga esta página en un momento si el estado no cambia.</span>
                    </div>
                @endif

                <div class="mt-4">
                    <h5 class="mb-3">Detalles de la reserva</h5>
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Pista</dt>
                        <dd class="col-sm-9">{{ $reservation->court->name }}</dd>

                        <dt class="col-sm-3">Fecha</dt>
                        <dd class="col-sm-9">{{ $reservation->start_time->translatedFormat('d \d\e F \d\e Y') }}</dd>

                        <dt class="col-sm-3">Hora</dt>
                        <dd class="col-sm-9">
                            {{ $reservation->start_time->format('H:i') }} - {{ $reservation->end_time->format('H:i') }}
                        </dd>

                        <dt class="col-sm-3">Precio</dt>
                        <dd class="col-sm-9">
                            {{ number_format($reservation->court->reservation_price, 2, ',', '.') }} €
                        </dd>

                        <dt class="col-sm-3">Estado del pago</dt>
                        <dd class="col-sm-9">
                            <span class="type-badge badge {{ $reservation->payment_status === App\Enums\PaymentStatus::Paid ? 'badge-green' : 'bg-warning' }}" 
                                style="display: inline-flex; 
                                align-items: center; 
                                gap: 6px;
                                font-size: 12px; 
                                font-weight: 600; 
                                padding: 4px 10px; 
                                border-radius: 999px;">
                                <span class="dot" 
                                        style="
                                        width: 6px;
                                        height: 6px;
                                        border-radius: 50%; 
                                        background:currentColor; "
                                ></span>
                                {{ $reservation->payment_status === App\Enums\PaymentStatus::Paid ? 'Pagado' : 'Pendiente' }}
                            </span>
                        </dd>
                    </dl>
                </div>

                <div class="mt-4">
                    <a href="{{ route('client.reservations.index') }}" class="btn btn-primary">
                        Ver mis reservas
                    </a>
                    <a href="{{ route('client.dashboard') }}" class="btn btn-outline-secondary ms-2">
                        Volver al inicio
                    </a>
                </div>
            </div>
        </div>
    </main>
@endsection