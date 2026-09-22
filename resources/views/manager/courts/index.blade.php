@extends('layouts.staff')

@section('title', 'Todas las pistas · Moral de Calatrava')

@section('titleHeader', 'Gestión de pistas · Moral de Calatrava')

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
                <i class="ti ti-soccer-field"></i>
                <div>
                    <h2>Lista de pistas</h2>
                    <small class="text-muted">Pistas disponibles con sus datos</small>
                </div>
            </div>
            {{-- Filtros --}}
            <div class="py-4">
            <x-filters.filter-bar :action="route('manager.courts.index')" :active-filters="$filters">
                <div class="col-md-2">
                    <select name="location" class="form-select">
                        <option value="">Todas las localizaciones</option>
                        @foreach($locations as $location)
                            <option value="{{ $location }}" @selected(($filters['location'] ?? '') === $location)>
                                {{ $location }}
                            </option>
                        @endforeach
                    </select>
                </div>
                {{-- CHIPS DE FILTROS --}}
                <x-slot:chips>
                    {{-- Chip de localización --}}
                    @if($filters['location'])
                        <x-filters.filter-chip
                            :label="'Localización: ' . ($filters['location'] ?? '')"
                            :remove-url="request()->fullUrlWithoutQuery('location')"
                        />
                    @endif
                </x-slot:chips>
            </x-filters.filter-bar>
            </div>

            @if($courts->isEmpty())
                <p class="text-muted mb-0">No hay pistas que coincidan con los filtros.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-hover text-nowrap">
                        <thead>
                            <tr>
                                {{-- Nombre --}}
                                <th>
                                    <a href="{{ \App\Helpers\SortHelper::url('name', $sortColumns) }}" class="sort-link" title="Ordenar por nombre">
                                        <span>Nombre</span>
                                        {!! \App\Helpers\SortHelper::icon('name', $sortColumns) !!}
                                    </a>
                                </th>
                                {{-- Localización --}}
                                <th>
                                    <a href="{{ \App\Helpers\SortHelper::url('location', $sortColumns) }}" class="sort-link" title="Ordenar por localización">
                                        <span>Localización</span>
                                        {!! \App\Helpers\SortHelper::icon('location', $sortColumns) !!}
                                    </a>
                                </th>
                                <th>Reservas</th>
                                <th class="text-center">Calendario</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                                {{-- Recorremos las pistas y las añadimos a la tabla --}} 
                                @foreach($courts as $index => $court)
                            <tr>
                                {{-- Nombre --}}
                                <td>{{ $court->name }}</td>
                                {{-- Localización --}}
                                <td>
                                    <span class="type-badge {{ $court->location === 'Polideportivo' ? 'blue' : 'green' }}">
                                        <i class="ti {{ $court->location === 'Polideportivo' ? 'ti-soccer-field' : 'ti-building-stadium' }}" aria-hidden="true"></i>
                                        {{ $court->location }}
                                    </span>
                                </td>
                                {{-- Consultar reservas --}}
                                <td>
                                    <a class="btn btn-primary form-floating nav-item" href="{{ route('manager.reservations.index', ['court_id' => $court->id]) }}">
                                        Consultar reservas
                                    </a>
                                </td>
                                {{-- Calendario de la pista --}}
                                <td class="text-center">
                                    <a class="btn btn-outline-primary form-floating nav-item" href="{{ route('manager.courts.calendar', $court) }}">Calendario</a>
                                </td>
                                {{-- Acciones --}}
                                <td class="text-center">
                                    <div class="d-inline-flex gap-1">
                                        <a
                                            href="{{ route('manager.reservations.create', ['court_id' => $court->id]) }}"
                                            class="btn btn-icon btn-outline-success btn-sm"
                                            data-bs-toggle="tooltip"
                                            title="Añadir reserva"
                                            aria-label="Añadir reserva a {{ $court->name }}"
                                        >
                                            <i class="ti ti-plus" aria-hidden="true"></i>
                                        </a>
                                        <a
                                            href="{{ route('manager.courts.edit', $court) }}"
                                            class="btn btn-icon btn-outline-secondary btn-sm"
                                            data-bs-toggle="tooltip"
                                            title="Editar pista"
                                            aria-label="Editar {{ $court->name }}"
                                        >
                                            <i class="ti ti-edit" aria-hidden="true"></i>
                                        </a>
                                        <form method="POST" action="{{ route('manager.courts.destroy', $court) }}" onsubmit="return confirm('¿Seguro que quieres eliminar la pista {{ $court->name }}? Esta acción no se puede deshacer.');" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="btn btn-icon btn-outline-danger btn-sm"
                                                data-bs-toggle="tooltip"
                                                title="Eliminar pista"
                                                aria-label="Eliminar {{ $court->name }}"
                                            >
                                                <i class="ti ti-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </div>    
                                </td>
                            </tr>
                                @endforeach
                        </tbody>
                    </table>
                    {{ $courts->links() }}
                </div>
            @endif
            <a class="btn btn-success form-floating nav-item" href="{{ route('manager.courts.create') }}">Añadir pista</a>
        </div>
    </div>
</main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                new bootstrap.Tooltip(el);
            });
        });
    </script>
@endpush