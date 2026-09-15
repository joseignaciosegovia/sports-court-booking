@extends('layouts.staff')

@section('title', 'Feedback de clientes · Moral de Calatrava')

@section('titleHeader', 'Gestión del feedback · Moral de Calatrava')

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
                <i class="ti ti-history" aria-hidden="true"></i>
                <div>
                    <h2>Historial de sugerencias/incidencias</h2>
                    <small class="text-muted">Consulta todas las sugerencias/incidencias enviadas por los clientes</small>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-4">
                {{-- Tipo --}}
                <div class="col-md-2">
                    <select name="type" class="form-select">
                        <option value="">Todas los tipos</option>
                        <option value="suggestion" @selected($filters['type'] == 'suggestion')>Sugerencia</option>
                        <option value="incident" @selected($filters['type'] == 'incident')>Incidencia</option>
                    </select>
                </div>
                {{-- Fecha concreta --}}
                <div class="col-md-2">
                    <input type="date" name="date" value="{{ $filters['date'] }}" class="form-control">
                </div>
                {{-- Botón para filtrar --}}
                <div class="w-auto y px-4">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
            </form>

            @if($feedback->isEmpty())
                <p class="text-muted mb-0">No hay sugerencias/incidencias que coincidan con los filtros.</p>
            @else
            <div class="table-responsive">
                <table class="table table-striped table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>#</th>
                            {{-- Tipo --}}
                            <th>
                                <a href="{{ \App\Helpers\SortHelper::url('type', $sortColumns) }}" class="sort-link" title="Ordenar por tipo">
                                    <span>Tipo</span>
                                    {!! \App\Helpers\SortHelper::icon('type', $sortColumns) !!}
                                </a>
                            </th>
                            {{-- Fecha --}}
                            <th>
                                <a href="{{ \App\Helpers\SortHelper::url('date', $sortColumns) }}" class="sort-link" title="Ordenar por fecha">
                                    <span>Fecha</span>
                                    {!! \App\Helpers\SortHelper::icon('date', $sortColumns) !!}
                                </a>
                            </th>
                            {{-- Usuario --}}
                            <th>
                                <a href="{{ \App\Helpers\SortHelper::url('user', $sortColumns) }}" class="sort-link" title="Ordenar por usuario">
                                    <span>Usuario</span>
                                    {!! \App\Helpers\SortHelper::icon('user', $sortColumns) !!}
                                </a>
                            </th>
                            {{-- Contenido --}}
                            <th>
                                <a href="{{ \App\Helpers\SortHelper::url('content', $sortColumns) }}" class="sort-link" title="Ordenar por contenido">
                                    <span>Contenido</span>
                                    {!! \App\Helpers\SortHelper::icon('content', $sortColumns) !!}
                                </a>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Recorremos y mostramos las incidencias --}} 
                        @foreach($feedback as $index => $item)
                        <tr>
                            <th>{{ $feedback->firstItem() + $index }}</th>
                            @if($item->type == "suggestion")
                                <td><span class="type-badge blue"><span class="dot"></span>Sugerencia</span></td>
                            @else
                                <td><span class="type-badge red"><span class="dot"></span>Incidencia</span></td>
                            @endif
                            <td>{{ $item->created_at->timezone('Europe/Madrid')->format('Y-m-d') }} · {{ $item->created_at->timezone('Europe/Madrid')->format('H:i') }}</td>
                            <td>{{ $item->user->name }}</td>
                            <!-- Ajustamos el ancho de la última columna al contenido con style -->
                            <td style="width: 1%; white-space: nowrap;">{{ $item->content }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $feedback->links() }}
            </div>
            @endif
        </div>
    </div>
</main>
@endsection