@extends('layouts.staff')

@section('title', 'Editar reserva · Moral de Calatrava')

@section('titleHeader', 'Gestión de reservas · Moral de Calatrava')

@section('manager-content')
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
    <div class="card shadow-sm border-0">
        <div class="p-3 py-4">
            <div class="seccionSubtitulo">
                <i class="ti ti-calendar"></i>
                <div>
                    <h2>Editar la reserva de la pista {{ $reservation->court->name }} en la fecha {{ $reservation->start_time }}</h2>
                    <small class="text-muted">Modifica los datos de la reserva</small>
                </div>
            </div>
        
            <form method="POST" action="{{ route('manager.reservations.update', $reservation) }}" name="editarReserva" enctype="multipart/form-data" id="form-update-reservations">
                @csrf
                @method('PUT')
                <div class="p-3 py-5">
                    <div class="row mt-3">
                        <div class="col-12 col-sm-6 mt-3 mt-sm-0">
                            <label for="information" class="labels">Información</label>
                            <input type="text" class="form-control" id="information" name="information" placeholder="Ej.: Partido de fútbol" value="{{ old('information', $reservation->information) }}" 
                                @if($reservation->user_id)
                                    disabled 
                                @endif
                                >
                            @if($reservation->user_id)
                                <small class="text-muted">Este campo no se puede editar porque la reserva pertenece a un cliente.</small>
                            @endif
                            @error('information') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="date" class="labels">Fecha</label>
                            <input type="date" class="form-control" id="date" name="date" placeholder="{{ now() }}" value="{{ old('start_time', $reservation->start_time->format('Y-m-d')) }}" required>
                            @error('date') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12 col-sm-6">
                            <label for="start_time_only" class="labels">Hora de inicio</label>
                            @if($reservation->user_id)
                                <select class="form-select" id="start_time_only" name="start_time_only" required>
                                    @php
                                        $current = old('start_time_only', $reservation->start_time->format('H:i'));
                                    @endphp
                                    @for($h = (int) explode(':', $openingTime)[0]; $h < (int) explode(':', $closingTime)[0]; $h++)
                                        @php $time = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00'; @endphp
                                        <option value="{{ $time }}" {{ $current === $time ? 'selected' : '' }}>{{ $time }}</option>
                                    @endfor
                                </select>
                            @else
                                <input type="time" class="form-control" id="start_time_only" name="start_time_only"
                                    placeholder="08:00"
                                    value="{{ old('start_time_only', $reservation->start_time->format('H:i')) }}"
                                    min="{{ $openingTime }}" max="{{ $closingTime }}" required>
                            @endif
                            @error('start_time_only') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-sm-6 mt-3 mt-sm-0">
                            <label for="end_time_only" class="labels">Hora de fin</label>
                            @if($reservation->user_id)
                                <select class="form-select" id="end_time_only" name="end_time_only" disabled>
                                    {{-- Se rellena por JS a partir de start_time_only --}}
                                </select>
                                {{-- Campo oculto para que el valor SÍ viaje en el POST, ya que "disabled" no se envía --}}
                                <input type="hidden" id="end_time_only_hidden" name="end_time_only" value="{{ old('end_time_only', $reservation->end_time->format('H:i')) }}">
                                <small class="text-muted">La hora de fin se calcula automáticamente (+1 hora) porque la reserva pertenece a un cliente.</small>
                            @else
                                <input type="time" class="form-control" id="end_time_only" name="end_time_only"
                                    placeholder="09:00"
                                    value="{{ old('end_time_only', $reservation->end_time->format('H:i')) }}"
                                    min="{{ $openingTime }}" max="{{ $closingTime }}" required>
                            @endif
                            @error('end_time_only') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </form>
            <div class="mt-5 d-flex justify-content-center gap-3">
                <form method="POST" action="{{ route('manager.reservations.cancel', $reservation) }}" onsubmit="return confirm('¿Seguro que quieres eliminar esta reserva? Esta acción no se puede deshacer.');">
                    @csrf
                    @method('PATCH')
                    <label for="reason" class="form-label">Motivo de la cancelación</label>
                    <input type="text" name="reason" id="reason" class="form-control mb-2" placeholder="Ej.: Avería en la instalación" required>
                    <button type="submit" class="btn btn-warning">Cancelar reserva {{ $reservation->user_id  === null ? '' : ' y reembolsar' }}</button>
                </form>

                <button type="submit" form="form-update-reservations" class="btn btn-success">Actualizar reserva</button>
            </div>
        </div>
    </div>
    <div class="mt-2 text-start">
        <a href="{{ route('manager.reservations.index') }}" class="btn btn-secondary">Volver atrás</a>
    </div>
</main>
@endsection

@push('scriptsPie')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const isClientReservation = {{ $reservation->user_id ? 'true' : 'false' }};

        if (!isClientReservation) {
            return;
        }

        const startSelect = document.getElementById('start_time_only');
        const endSelect = document.getElementById('end_time_only');
        const endHidden = document.getElementById('end_time_only_hidden');

        function recalcularFin() {
            if (!startSelect.value) return;

            const [hour] = startSelect.value.split(':').map(Number);
            const endValue = String(hour + 1).padStart(2, '0') + ':00';

            endSelect.innerHTML = '';
            endSelect.appendChild(new Option(endValue, endValue));
            endSelect.value = endValue;

            endHidden.value = endValue;
        }

        startSelect.addEventListener('change', recalcularFin);

        recalcularFin();
    });
</script>
@endpush