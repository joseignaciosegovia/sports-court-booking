@extends('layouts.staff')

@section('title', 'Editar pista · Moral de Calatrava')

@section('titleHeader', 'Gestión de pistas · Moral de Calatrava')

@push('styles')
    @vite('resources/css/form.css')
@endpush

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
                    <h2>Editar la pista<span class="ms-2 fw-bold text-primary">{{ $court->name }}</span></h2>
                    <small class="text-muted">Modifica los datos de la pista {{ $court->name }}</small>
                </div>
            </div>
            <form method="POST" action="{{ route('manager.courts.update', $court) }}" class="needs-validation" name="editarPista" enctype="multipart/form-data" id="form-update-court" novalidate>
                @csrf
                @method('PUT')
                <div class="p-3 py-4">
                    <div class="row mt-3">
                        <div class="col-12 col-sm-6">
                            <label for="name" class="labels">Nombre</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" placeholder="Nombre de la pista" value="{{ old('name', $court->name) }}" required>
                            <div class="invalid-feedback">
                                {{ $errors->first('name') ?: 'Introduce el nombre de la pista.' }}
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 mt-3 mt-sm-0">
                            <label for="reservation_price" class="labels">Precio de Reserva</label>
                            <input type="number" class="form-control @error('reservation_price') is-invalid @enderror" id="reservation_price" name="reservation_price" placeholder="0,00" step="0.01" value="{{ old('reservation_price', $court->reservation_price) }}" required>
                            <div class="invalid-feedback">
                                {{ $errors->first('reservation_price') ?: 'El precio debe ser un número.' }}
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12 col-sm-6">
                            <label for="location">Localización</label><br>
                            <select class="form-select" name="location" id="location">
                                {{-- Recorremos las localizaciones --}} 
                                @foreach($locations as $location)
                                    {{-- Añadimos cada localización al select, seleccionada la localización de la pista --}}
                                    <option value="{{ $location }}" @selected(old('location', $court->location) === $location)>
                                        {{ $location }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </form>
            <div class="mt-5 d-flex justify-content-center gap-3">
                <form method="POST" action="{{ route('manager.courts.destroy', $court) }}" onsubmit="return confirm('¿Seguro que quieres eliminar esta pista? Esta acción no se puede deshacer.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Borrar pista</button>
                </form>

                <button type="submit" form="form-update-court" class="btn btn-success">Actualizar pista</button>
            </div>
        </div>
    </div>
    <div class="mt-2 text-start">
        <a href="{{ route('manager.courts.index') }}" class="btn btn-secondary">Volver atrás</a>
    </div>
</main>
@endsection

@push('scripts')
    @vite('resources/js/validation.js')
@endpush