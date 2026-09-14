@extends('layouts.staff')

@section('title', 'Editar gestor · Moral de Calatrava')

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
                <i class="ti ti-user-cog" aria-hidden="true"></i>
                <div>
                    <h2>Gestor/a {{ $manager->name }}</h2>
                    <small class="text-muted">Modifica los datos del gestor {{ $manager->name }}</small>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.managers.update', $manager) }}" enctype="multipart/form-data" id="form-update">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Nombre</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $manager->name) }}" autocomplete="name" required>
                            @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="dni" class="form-label">DNI</label>
                            <input type="text" name="dni" id="dni" class="form-control" value="{{ old('dni', $manager->dni) }}" required>
                            @error('dni') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $manager->email) }}" autocomplete="name" required>
                            @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Mínimo 8 caracteres">
                            <small class="text-muted">La contraseña debe tener al menos 8 caracteres</small>
                            @error('password') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Repite la contraseña">
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label">Teléfono (opcional)</label>
                            <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone', $manager->phone) }}" autocomplete="name">
                            @error('phone') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="role" class="form-label">Rol</label>
                            <select name="role" id="role" class="form-select" required>
                                <option value="manager" @selected(old('role', $manager->role) === 'manager')>Gestor</option>
                                <option value="admin" @selected(old('role', $manager->role) === 'admin')>Administrador</option>
                            </select>
                            @error('role') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="photo" class="form-label">Foto de perfil (opcional)</label>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                @if($manager->photo)
                                    <img src="{{ $manager->photo_url }}" alt="Foto actual" width="50" height="50" class="rounded-circle" style="object-fit: cover;">
                                    <span class="text-muted">Foto actual</span>
                                @endif
                            </div>
                            <input type="file" name="photo" id="photo" class="form-control">
                            @error('photo') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <br>
                        <div>
                            <input type="checkbox" name="delete_photo" id="delete_photo" value="1">
                            <label for="delete_photo">
                                Eliminar foto de perfil
                            </label>
                        </div>
                    </div>
                </form>

                <div class="mt-4 d-flex justify-content-between align-items-center">
                    {{-- Solo aparecerá el botón de borrado si el administrador no se está editando a sí mismo --}}
                    @if($manager->id !== auth()->id())
                        <form method="POST" action="{{ route('admin.managers.destroy', $manager) }}" onsubmit="return confirm('¿Seguro que quieres eliminar a este gestor? Esta acción no se puede deshacer.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Borrar gestor</button>
                        </form>
                    @else
                        <span></span> {{-- mantiene el espaciado del flex --}}
                    @endif

                    {{-- Aunque el botón esté fuera del formulario, con el atributo 'form' se asocia al formulario que tenga el 'id' con el mismo valor --}}
                    <button type="submit" form="form-update" class="btn btn-success">Actualizar gestor</button>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-2 text-start">
        <a href="{{ route('admin.managers.index') }}" class="btn btn-secondary">Volver atrás</a>
    </div>
</main>
</div>
@endsection