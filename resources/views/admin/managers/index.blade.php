@extends('layouts.staff')

@section('title', 'Administración de gestores · Moral de Calatrava')

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
                <i class="ti ti-user-cog"></i>
                <div>
                    <h2>Lista de Gestores</h2>
                    <small class="text-muted">Consulta y modifica los datos de los gestores</small>
                </div>
            </div>
            <hr class="mt-0 mb-4" style="border-color: #dee2e6;">
                {{-- Filtros --}}
                <form method="GET" class="row g-2 mb-4">
                    {{-- Tipos --}}
                    <div class="col-md-2">
                        <select name="role" class="form-select">
                            <option value="">Todos los roles</option>
                            <option value="manager" @selected($filters['role'] === 'manager')>Gestor</option>
                            <option value="admin" @selected($filters['role'] === 'admin')>Administrador</option>
                        </select>
                    </div>
                    {{-- Botón para filtrar --}}
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    </div>
                </form>

            @if($managers->isEmpty())
                <p class="text-muted mb-0">No hay gestores/administradores que coincidan con los filtros.</p>
            @else
            <div class="table-responsive">
                <table class="table table-striped table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>#</th>
                            {{-- Email --}}
                            <th>
                                <a href="{{ \App\Helpers\SortHelper::url('email', $sortColumns) }}" class="sort-link" title="Ordenar por email">
                                    <span>Email</span>
                                    {!! \App\Helpers\SortHelper::icon('email', $sortColumns) !!}
                                </a>
                            </th>
                            {{-- Nombre --}}
                            <th>
                                <a href="{{ \App\Helpers\SortHelper::url('name', $sortColumns) }}" class="sort-link" title="Ordenar por nombre">
                                    <span>Nombre</span>
                                    {!! \App\Helpers\SortHelper::icon('name', $sortColumns) !!}
                                </a>
                            </th>
                            {{-- DNI --}}
                            <th>
                                <a href="{{ \App\Helpers\SortHelper::url('dni', $sortColumns) }}" class="sort-link" title="Ordenar por DNI">
                                    <span>DNI</span>
                                    {!! \App\Helpers\SortHelper::icon('dni', $sortColumns) !!}
                                </a>
                            </th>
                            {{-- Teléfono --}}
                            <th>
                                <a href="{{ \App\Helpers\SortHelper::url('phone', $sortColumns) }}" class="sort-link" title="Ordenar por teléfono">
                                    <span>Teléfono</span>
                                    {!! \App\Helpers\SortHelper::icon('phone', $sortColumns) !!}
                                </a>
                            </th>
                            {{-- Rol --}}
                            <th>
                                <a href="{{ \App\Helpers\SortHelper::url('role', $sortColumns) }}" class="sort-link" title="Ordenar por rol">
                                    <span>Rol</span>
                                    {!! \App\Helpers\SortHelper::icon('role', $sortColumns) !!}
                                </a>
                            </th>
                            <th>Editar gestor</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Recorremos los gestores y los añadimos a la tabla --}}
                        @foreach($managers as $index => $manager)
                        <tr>
                            <th>{{ $managers->firstItem() + $index }}</th>
                            <td>{{ $manager->email }}</td>
                            <td>{{ $manager->name }}</td>
                            <td>{{ $manager->dni }}</td>
                            <td>{{ $manager->phone ?? '-' }}</td>
                            <td>
                                {{-- Si el gestor es también administrador lo indicamos --}} 
                                @if($manager->role == 'admin')
                                    {{ "Administrador" }}
                                @else
                                    {{ "Gestor" }}
                                @endif
                            </td>
                            <td><a href="{{ route('admin.managers.edit', $manager) }}" class="btn btn-warning btn-sm">Editar</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $managers->links() }}
            </div>
            @endif
            <a href="{{ route('admin.managers.create') }}" class="btn btn-success">
                Añadir gestor
            </a>
        </div>
    </div>
</main>
</div>
@endsection