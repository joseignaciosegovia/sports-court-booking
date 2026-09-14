@extends('layouts.client')

@section('title', 'Pago cancelado · Moral de Calatrava')

@section('titleHeader', 'Reservas · Moral de Calatrava')

@section('client-content')
    <main class="main">
        <div class="card shadow-sm border-0">
            <div class="p-3 py-4">
                <div class="seccionSubtitulo">
                    <i class="ti ti-circle-x" aria-hidden="true"></i>
                    <div>
                        <h2>Pago cancelado</h2>
                        <small class="text-muted">No se ha completado el pago de tu reserva</small>
                    </div>
                </div>

                <div class="alert alert-danger d-flex align-items-center gap-2">
                    <i class="ti ti-alert-circle" aria-hidden="true"></i>
                    <span>Has cancelado el proceso de pago. Tu reserva no está confirmada.</span>
                </div>

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
                    </dl>
                </div>

                <div class="mt-4">
                    <a href="{{ route('client.reservations.create') }}" class="btn btn-primary">
                        Intentar de nuevo
                    </a>
                    <a href="{{ route('client.dashboard') }}" class="btn btn-outline-secondary ms-2">
                        Volver al inicio
                    </a>
                </div>
            </div>
        </div>
    </main>
@endsection