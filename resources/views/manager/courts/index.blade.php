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
            <div class="table-responsive">
                <table class="table table-striped table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>#</th>
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
                            <th>Calendario</th>
                            <th>Añadir reserva</th>
                            <th>Editar pista</th>
                        </tr>
                    </thead>
                    <tbody>
                            {{-- Recorremos las pistas y las añadimos a la tabla --}} 
                            @foreach($courts as $index => $court)
                        <tr>
                            <th>{{ $courts->firstItem() + $index }}</th>
                            <td>{{ $court->name }}</td>
                            <td>{{ $court->location }}</td>
                            <td><a class="btn btn-primary form-floating nav-item" href="{{ route('manager.reservations.index', ['court_id' => $court->id]) }}">
                                    <i class="ti ti-calendar" aria-hidden="true"></i>
                                    Consultar reservas
                                </a>
                            </td>
                            <td><a class="btn btn-outline-primary form-floating nav-item" href="{{ route('manager.courts.calendar', $court) }}">Calendario</a></td>
                            <td><a href="{{ route('manager.reservations.create', ['court_id' => $court->id]) }}" class="btn btn-success"><i class="ti ti-plus" aria-hidden="true"></i> Añadir reserva</a></td>
                            <td><a class="btn btn-warning form-floating nav-item" href="{{ route('manager.courts.edit', $court) }}">Editar</a></td>
                        </tr>
                            @endforeach
                    </tbody>
                </table>
                {{ $courts->links() }}
            </div>
            <a class="btn btn-success form-floating nav-item" href="{{ route('manager.courts.create') }}">Añadir pista</a>
        </div>
    </div>
</main>
@endsection