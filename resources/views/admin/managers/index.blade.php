@extends('layouts.staff')

@section('title', 'Administración de gestores · Moral de Calatrava')

@section('titleHeader', 'Administración de gestores · Moral de Calatrava')

@push('styles')
    @vite('resources/css/table.css')
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
                <i class="ti ti-user-cog"></i>
                <div>
                    <h2>Lista de Gestores</h2>
                    <small class="text-muted">Consulta y modifica los datos de los gestores</small>
                </div>
            </div>
            <hr class="mt-0 mb-4" style="border-color: #dee2e6;">
                {{-- Filtros --}}
                <x-filters.filter-bar :action="route('admin.managers.index')" :active-filters="$filters">
                    {{-- Rol --}}
                    <div class="col-md-2">
                        <select name="role" class="form-select">
                            <option value="">Todos los roles</option>
                            <option value="{{ \App\Enums\UserRole::Manager->value }}" @selected($filters['role'] === \App\Enums\UserRole::Manager->value)>
                                {{ \App\Enums\UserRole::Manager->label() }}
                            </option>
                            <option value="{{ \App\Enums\UserRole::Admin->value }}" @selected($filters['role'] === \App\Enums\UserRole::Admin->value)>
                                {{ \App\Enums\UserRole::Admin->label() }}
                            </option>
                        </select>
                    </div>
                    {{-- CHIPS DE FILTROS --}}
                    <x-slot:chips>
                        {{-- Chip de rol --}}
                        @if(!empty($filters['role']))
                            @php
                                $role = \App\Enums\UserRole::tryFrom($filters['role']);
                            @endphp

                            @if($role)
                                <x-filters.filter-chip
                                    :label="'Rol: ' . $role->label()"
                                    :remove-url="request()->fullUrlWithoutQuery('role')"
                                />
                            @endif
                        @endif
                    </x-slot:chips>
                </x-filters.filter-bar>

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
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Recorremos los gestores y los añadimos a la tabla --}}
                        @foreach($managers as $index => $manager)
                        <tr>
                            <th>{{ $managers->firstItem() + $index }}</th>
                            {{-- Email --}}
                            <td>{{ $manager->email }}</td>
                            {{-- Nombre --}}
                            <td>{{ $manager->name }}</td>
                            {{-- DNI --}}
                            <td>{{ $manager->dni }}</td>
                            {{-- Teléfono --}}
                            <td>{{ $manager->phone ?? '-' }}</td>
                            {{-- Rol --}}
                            <td>
                                <span class="type-badge {{ $manager->role->badgeColor() }}">
                                    {{ $manager->role->label() }}
                                </span>
                            </td>
                            {{-- Acciones --}}
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    {{-- Editar gestor --}}
                                    <a
                                        href="{{ route('admin.managers.edit', $manager) }}"
                                        class="btn btn-icon btn-outline-secondary btn-sm"
                                        data-bs-toggle="tooltip"
                                        title="Editar gestor"
                                        aria-label="Editar {{ $manager->name }}"
                                    >
                                        <i class="ti ti-edit" aria-hidden="true"></i>
                                    </a>
                                    {{-- Eliminar gestor --}}
                                    {{-- Solo aparecerá el botón de borrado si el administrador no se está editando a sí mismo --}}
                                    @if($manager->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.managers.destroy', $manager) }}" onsubmit="return confirm('¿Seguro que quieres eliminar el gestor {{ $manager->name }}? Esta acción no se puede deshacer.');" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="btn btn-icon btn-outline-danger btn-sm"
                                                data-bs-toggle="tooltip"
                                                title="Eliminar gestor"
                                                aria-label="Eliminar {{ $manager->name }}"
                                            >
                                                <i class="ti ti-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>    
                            </td>
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