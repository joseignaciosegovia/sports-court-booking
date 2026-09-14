@extends('layouts.client')

@section('title', 'Perfil de usuario · Moral de Calatrava')

@section('titleHeader', 'Perfil de usuario · Moral de Calatrava')

@push('scriptsCabecera')
    @vite('resources/css/form.css')
@endpush

@section('client-content')
    <main class="main">
        <!-- BIENVENIDA -->
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
            <form method="POST" action="{{ route('client.profile.update', $authUser) }}" name="perfilCliente" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="p-3 py-4">
                    <div class="seccionSubtitulo">
                        <i class="ti ti-user"></i>
                        <div>
                            <h2>Información del perfil</h2>
                            <small class="text-muted">Modifica tus datos personales y de acceso</small>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12 col-sm-6">
                            <label for="name" class="labels">Nombre completo</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Nombre completo" value="{{ $authUser->name }}" autocomplete="off" required>
                            @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-sm-6 mt-3 mt-sm-0">
                            <label for="dni" class="labels">DNI</label>
                            <input type="text" class="form-control" id="dni" name="dni" placeholder="12345678A" pattern="[0-9]{8}[A-Z]" value="{{ $authUser->dni }}" required>
                            @error('dni') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12 col-sm-6">
                            <label for="password" class="labels">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Mínimo 8 caracteres" pattern=".{8,}" value="">
                            @error('password') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-sm-6 mt-3 mt-sm-0">
                            <label for="password_confirmation" class="labels">Confirmar contraseña</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Repite la contraseña" value="">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12 col-sm-6">
                            <label for="phone" class="labels">Teléfono (opcional)</label>
                            <input type="tel" class="form-control" id="phone" name="phone" placeholder="600 000 000" pattern="[0-9]{9}" value="{{ $authUser->phone }}" autocomplete="off">
                            @error('phone') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-sm-6 mt-3 mt-sm-0">
                            <label for="photo" class="labels">Foto de perfil (opcional)</label>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <img class="rounded-circle" src="{{ $authUser->photo_url }}" alt="Foto de perfil" width="60" height="60" style="object-fit:cover;">
                                <span class="text-muted small">Foto actual</span>
                            </div>
                            <input type="file" class="form-control" id="photo" name="photo">
                            @error('photo') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <input type="checkbox" name="delete_photo" id="delete_photo" value="1">
                            <label for="delete_photo">
                                Eliminar foto de perfil
                            </label>
                        </div>
                    </div>
                    <div class="mt-4 d-flex justify-content-end">
                        <button class="btn btn-success px-4" type="submit" name="Actualizar">Actualizar perfil</button>
                    </div>
                </div>
            </form>
        </div>
    </main>
@endsection