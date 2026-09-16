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
            <x-filters.filter-bar :action="route('manager.feedback.index')" :active-filters="$filters">
                {{-- Tipo --}}
                <div class="col-md-2">
                    <select name="type" class="form-select">
                        <option value="">Todas los tipos</option>
                        <option value="{{ \App\Enums\FeedbackType::Suggestion->value }}" @selected($filters['type'] === \App\Enums\FeedbackType::Suggestion->value)>
                            {{ \App\Enums\FeedbackType::Suggestion->label() }}
                        </option>
                        <option value="{{ \App\Enums\FeedbackType::Incident->value }}" @selected($filters['type'] === \App\Enums\FeedbackType::Incident->value)>
                            {{ \App\Enums\FeedbackType::Incident->label() }}
                        </option>
                    </select>
                </div>
                {{-- Fecha concreta --}}
                <div class="col-md-2">
                    <input type="date" name="date" value="{{ $filters['date'] }}" class="form-control">
                </div>
                {{-- CHIPS DE FILTROS --}}
                <x-slot:chips>
                    {{-- Chip de tipo --}}
                    @if(!empty($filters['type']))
                        @php
                            $feedbackType = \App\Enums\FeedbackType::tryFrom($filters['type']);
                        @endphp

                        @if($feedbackType)
                            <x-filters.filter-chip
                                :label="'Tipo: ' . $feedbackType->label()"
                                :remove-url="request()->fullUrlWithoutQuery('type')"
                            />
                        @endif
                    @endif

                    {{-- Chip de fecha concreta --}}
                    @if(!empty($filters['date']))
                        @php
                            try {
                                $formattedDate = \Carbon\Carbon::parse($filters['date'])->format('Y-m-d');
                            } catch (\Exception $e) {
                                $formattedDate = null;
                            }
                        @endphp

                        @if($formattedDate)
                            <x-filters.filter-chip
                                :label="'Fecha: ' . $formattedDate"
                                :remove-url="request()->fullUrlWithoutQuery('date')"
                            />
                        @endif
                    @endif
                </x-slot:chips>
            </x-filters.filter-bar>

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
                            {{-- Tipo --}}
                            <td>
                                <span class="type-badge {{ $item->type->badgeColor() }}">
                                    <i class="{{ $item->type->badgeIcon() }}" aria-hidden="true"></i>
                                    {{ $item->type->label() }}
                                </span>
                            </td>
                            {{-- Fecha --}}
                            <td>{{ $item->created_at->timezone('Europe/Madrid')->format('Y-m-d') }} · {{ $item->created_at->timezone('Europe/Madrid')->format('H:i') }}</td>
                            {{-- Usuario --}}
                            <td>{{ $item->user->name }}</td>
                            <!-- Ajustamos el ancho de la última columna al contenido con style -->
                            {{-- Contenido --}}
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