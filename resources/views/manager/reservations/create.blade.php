@extends('layouts.staff')

@section('title', 'Crear reserva · Moral de Calatrava')

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
                <i class="ti ti-plus"></i>
                <div>
                    <h2>Nueva reserva</h2>
                    <small class="text-muted">Introduce los datos del nuevo horario reservado</small>
                </div>
            </div>
        
            <form method="POST" action="{{ route('manager.reservations.store') }}" name="crearReserva" id="form-create-reservation">
                @csrf

                <div class="p-3 py-2">
                    <div class="row mt-3">
                        <div class="col-12 col-sm-6">
                            <label for="court_id" class="labels">Pista</label>
                            <select class="form-select" id="court_id" name="court_id" required>
                                <option value="" disabled @selected(!old('court_id', $selectedCourtId))>Selecciona una pista</option>
                                @foreach($courts as $court)
                                    <option value="{{ $court->id }}" @selected(old('court_id', $selectedCourtId) == $court->id)>
                                        {{ $court->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('court_id') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-sm-6 mt-3 mt-sm-0">
                            <label for="date" class="labels">Fecha</label>
                            <input type="date" class="form-control" id="date" name="date" value="{{ old('date') }}" required>
                            @error('date') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12 col-sm-6">
                            <label for="start_time_only" class="labels">Hora de inicio</label>
                            <input type="time" class="form-control" id="start_time_only" name="start_time_only" placeholder="08:00" value="{{ old('start_time_only') }}" required>
                            @error('start_time_only') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-sm-6 mt-3 mt-sm-0">
                            <label for="end_time_only" class="labels">Hora de fin</label>
                            <input type="time" class="form-control" id="end_time_only" name="end_time_only" placeholder="09:00" value="{{ old('end_time_only') }}" required>
                            @error('end_time_only') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <label for="information" class="labels">Información</label>
                            <input type="text" class="form-control" id="information" name="information" placeholder="Ej.: Partido de baloncesto, mantenimiento, evento..." value="{{ old('information') }}">
                            @error('information') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </form>

            <div class="mt-4 d-flex justify-content-center">
                <button type="submit" form="form-create-reservation" class="btn btn-success">Crear reserva</button>
            </div>
        </div>
    </div>
</main>
@endsection