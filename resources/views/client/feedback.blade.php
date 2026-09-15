@extends('layouts.client')

@section('title', 'Feedback · Moral de Calatrava')

@section('titleHeader', 'Feedback · Moral de Calatrava')

@push('styles')
    @vite([
        'resources/css/form.css',
        'resources/css/table.css'
    ])
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
            <form method="POST" action="{{ route('client.feedback.store') }}" name="feedback" enctype="multipart/form-data">
                @csrf
                <div class="p-3 py-4">
                    <div class="seccionSubtitulo">
                        <i class="ti ti-mail"></i>
                        <div>
                            <h2>Enviar sugerencias e incidencias</h2>
                            <small class="text-muted">Realiza una sugerencia o envía una incidencia</small>
                        </div>
                    </div>
                    <hr class="mt-0 mb-4" style="border-color: #dee2e6;">

                    <div>
                        <div>
                            <label for="content" class="labels">Sugerencia o incidencia</label>
                            <textarea class="form-control" id="content" name="content" placeholder="Describe el problema o tu propuesta con el máximo detalle posible" required></textarea>
                            @error('content') <div class="text-danger small">{{ $message }}</div> @enderror
                            <br>
                            
                            <label for="type" class="labels">¿Es una sugerencia o una incidencia?</label>
                            <select class="form-select" name="type" id="type">
                                <option value="suggestion">Sugerencia</option>
                                <option value="incident">Incidencia</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4 d-flex justify-content-end">
                        <button class="btn btn-success px-4" type="submit" name="Enviar">Enviar</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="card shadow-sm border-0">
            <div class="p-3 pt-4">
                <div class="seccionSubtitulo">
                    <i class="ti ti-history" aria-hidden="true"></i>
                    <div>
                        <h2>Historial de sugerencias/incidencias</h2>
                        <small class="text-muted">Consulta todas las sugerencias/incidencias enviadas anteriormente</small>
                    </div>
                </div>
                <hr class="mt-0 mb-4" style="border-color: #dee2e6;">
                {{-- Filtros --}}
                <form method="GET" class="row g-2 mb-4">
                    {{-- Tipos --}}
                    <div class="col-md-2">
                        <select name="type" class="form-select">
                            <option value="">Todos los tipos</option>
                            <option value="suggestion" @selected($filters['type'] == 'suggestion')>Sugerencia</option>
                            <option value="incident" @selected($filters['type'] == 'incident')>Incidencia</option>
                        </select>
                    </div>
                    {{-- Fecha concreta --}}
                    <div class="col-md-2">
                        <input type="date" name="date" value="{{ $filters['date'] }}" class="form-control">
                    </div>
                    {{-- Botón para filtrar --}}
                    <div class="col-md-1">
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
                                <th>Número</th>
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
                            {{-- Recorremos las incidencias --}}
                            @foreach($feedback as $index => $item)
                            <tr>
                                <th class="col-num">{{ $feedback->firstItem() + $index }}</th>
                                @if($item->type == "suggestion")
                                    <td><span class="type-badge blue"><span class="dot"></span>Sugerencia</span></td>
                                @else
                                    <td><span class="type-badge red"><span class="dot"></span>Incidencia</span></td>
                                @endif
                                <td>{{ $item->created_at->timezone('Europe/Madrid')->format('Y-m-d') }} · {{ $item->created_at->timezone('Europe/Madrid')->format('H:i') }}</td>
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