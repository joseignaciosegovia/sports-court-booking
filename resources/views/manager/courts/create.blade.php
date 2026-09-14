@extends('layouts.staff')

@section('title', 'Crear pista · Moral de Calatrava')

@section('titleHeader', 'Gestión de pistas · Moral de Calatrava')

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
                <i class="ti ti-soccer-field"></i>
                <div>
                    <h2>Crear pista</h2>
                    <small class="text-muted">Introduce los datos de la nueva pista</small>
                </div>
            </div>
            <form method="POST" name="añadirPista" action="{{ route('manager.courts.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row mt-2">
                    <div class="col-12 col-sm-6">
                        <label for="name" class="labels">Nombre</label>
                        <input type="text" id="name" class="form-control" name="name" value="" placeholder="Nombre de la pista" required>
                        @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-sm-6 mt-3 mt-sm-0">
                        <label for="reservation_price" class="labels">Precio de Reserva</label>
                        <input type="number" class="form-control" id="reservation_price" name="reservation_price" value="" placeholder="0,00" step="0.01" required>
                        @error('reservation_price') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12 col-sm-6">
                        <label for="location">Localización</label><br>
                        <select class="form-select" name="location" id="location">
                             {{-- Recorremos las localizaciones --}} 
                            @foreach($locations as $location)
                                <option value="{{ $location }}" @selected(old('location') === $location)>
                                    {{ $location }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-5 text-center"><button class="btn btn-success profile-button" type="submit" name="Crear">Crear pista</button></div>
            </form>
        </div>
    </div>
    <div class="mt-2 text-start">
        <a href="{{ route('manager.courts.index') }}" class="btn btn-secondary">Volver atrás</a>
    </div>
</main>
@endsection