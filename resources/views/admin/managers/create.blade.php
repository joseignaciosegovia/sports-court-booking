@extends('layouts.staff')

@section('title', 'Crear gestor · Moral de Calatrava')

@section('titleHeader', 'Administración de gestores · Moral de Calatrava')

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
                <i class="ti ti-plus" aria-hidden="true"></i>
                <div>
                    <h2>Crear gestor</h2>
                    <small class="text-muted">Introduce los datos del nuevo gestor</small>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.managers.store') }}" class="needs-validation" enctype="multipart/form-data" novalidate>
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Nombre</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" placeholder="Nombre completo" value="{{ old('name') }}" required>
                            <div class="invalid-feedback">
                                {{ $errors->first('name') ?: 'Introduce tu nombre completo.' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" placeholder="correo@ejemplo.com" value="{{ old('email') }}" required>
                            <div class="invalid-feedback">
                                {{ $errors->first('email') ?: 'Introduce un correo electrónico válido.' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" placeholder="Mínimo 8 caracteres" required>
                            <small class="text-muted">La contraseña debe tener al menos 8 caracteres</small>
                            <div class="invalid-feedback">
                                {{ $errors->first('password') ?: 'La contraseña debe tener al menos 8 caracteres.' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control @error('password_confirmation') is-invalid @enderror" placeholder="Repite la contraseña" required>
                            <div class="invalid-feedback">
                                {{ $errors->first('password_confirmation') ?: 'Las contraseñas no coinciden.' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="dni" class="form-label">DNI</label>
                            <input type="text" name="dni" id="dni" class="form-control @error('dni') is-invalid @enderror" placeholder="12345678A" pattern="[0-9]{8}[A-Za-z]" oninput="this.value = this.value.toUpperCase()" value="{{ old('dni') }}" required>
                            <div class="invalid-feedback">
                                {{ $errors->first('dni') ?: 'El DNI debe tener 8 dígitos seguidos de una letra válida.' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="role" class="form-label">¿Es administrador?</label>
                            <select name="role" id="role" class="form-select @error('role') is-invalid @enderror" required>
                                <option value="manager" @selected(old('role') === 'manager')>No</option>
                                <option value="admin" @selected(old('role') === 'admin')>Sí</option>
                            </select>
                            <div class="invalid-feedback">
                                {{ $errors->first('role') ?: 'El rol debe ser "gestor" o "administrador"' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label">Teléfono (opcional)</label>
                            <input type="text" name="phone" id="phone" class="form-control" placeholder="600 000 000" value="{{ old('phone') }}">
                            <div class="invalid-feedback">
                                {{ $errors->first('phone') ?: 'El teléfono debe tener 9 dígitos.' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="photo" class="form-label">Foto de perfil (opcional)</label>
                            <input type="file" name="photo" id="photo" class="form-control">
                            <div class="invalid-feedback">
                                {{ $errors->first('photo') ?: 'La imagen no es válida o supera el tamaño máximo permitido.' }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-end">
                        <button type="submit" class="btn btn-success">Crear gestor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="mt-2 text-start">
        <a href="{{ route('admin.managers.index') }}" class="btn btn-secondary">Volver atrás</a>
    </div>
</main>
</div>
@endsection

@push('scripts')
    @vite('resources/js/validation.js')
@endpush