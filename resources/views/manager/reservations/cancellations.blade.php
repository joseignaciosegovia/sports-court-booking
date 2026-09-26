@extends('layouts.staff')

@section('title', 'Cancelaciones y devoluciones')

@section('titleHeader', 'Gestión de reservas · Moral de Calatrava')

@push('styles')
    @vite('resources/css/table.css')
@endpush

@section('manager-content')
<main class="main">
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
            <div class="seccionSubtitulo mb-4">
                <i class="ti ti-calendar-x" aria-hidden="true"></i>
                <div>
                    <h2>Reservas canceladas</h2>
                    <small class="text-muted">Historial de reservas canceladas</small>
                </div>
            </div>
            {{-- Filtros --}}
            <x-filters.filter-bar :action="route('manager.reservations.cancellations')" :active-filters="$filters">
                {{-- Pista --}}
                <div class="col-md-2">
                    <select name="court_id" class="form-select">
                        <option value="">Todas las pistas</option>
                        @foreach($courts as $court)
                            <option value="{{ $court->id }}" @selected($filters['court_id'] == $court->id)>
                                {{ $court->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                {{-- Reembolsada o sin devolución --}}
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="refunded" @selected($filters['status'] === 'refunded')>Reembolsada</option>
                        <option value="canceled" @selected($filters['status'] === 'canceled')>Sin devolución</option>
                    </select>
                </div>
                {{-- Autoría de la cancelación --}}
                <div class="col-md-2">
                    <select name="canceled_by" class="form-select">
                        <option value="">Cancelada por cualquiera</option>
                        <option value="manager" @selected($filters['canceled_by'] === 'manager')>Gestión</option>
                        <option value="client" @selected($filters['canceled_by'] === 'client')>Cliente</option>
                        <option value="system" @selected($filters['canceled_by'] === 'system')>Expiración automática</option>
                    </select>
                </div>
                {{-- Fecha concreta --}}
                <div class="col-md-2">
                    <input type="date" name="date" value="{{ $filters['date'] }}" class="form-control">
                </div>
                {{-- CHIPS DE FILTROS --}}
                <x-slot:chips>
                    {{-- Chip de pista --}}
                    @if($filters['court_id'])
                        <x-filters.filter-chip
                            :label="'Pista: ' . ($courts->find($filters['court_id'])->name ?? '')"
                            :remove-url="request()->fullUrlWithoutQuery('court_id')"
                        />
                    @endif
                    {{-- Chip de estado (reembolsada o sin devolución) --}}
                    @if($filters['status'])
                        <x-filters.filter-chip
                            :label="'Estado: ' . ($filters['status'] === 'refunded' ? 'Reembolsada' : 'Sin devolución')"
                            :remove-url="request()->fullUrlWithoutQuery('status')"
                        />
                    @endif
                    {{-- Chip de autoría de la cancelación --}}
                    @if($filters['canceled_by'])
                        <x-filters.filter-chip
                            :label="'Razón de la cancelación: ' . \App\Enums\CanceledBy::tryFrom($filters['canceled_by'])->label()"
                            :remove-url="request()->fullUrlWithoutQuery('canceled_by')"
                        />
                    @endif
                    {{-- Chip de fecha concreta --}}
                    @if($filters['date'])
                        <x-filters.filter-chip
                            :label="'Fecha: ' . ($filters['date'] ?? '')"
                            :remove-url="request()->fullUrlWithoutQuery('date')"
                        />
                    @endif
                </x-slot:chips>
            </x-filters.filter-bar>

            @if($cancellations->isEmpty())
                <p class="text-muted mb-0">No hay reservas que coincidan con los filtros.</p>
            @else
            <div class="table-responsive">
                <table class="table table-striped table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>#</th>
                            {{-- Pista --}}
                            <th>
                                <a href="{{ \App\Helpers\SortHelper::url('court', $sortColumns) }}" class="sort-link" title="Ordenar por pista">
                                    <span>Pista</span>
                                    {!! \App\Helpers\SortHelper::icon('court', $sortColumns) !!}
                                </a>
                            </th>
                            {{-- Fecha reserva --}}
                            <th>
                                <a href="{{ \App\Helpers\SortHelper::url('reservation_date', $sortColumns) }}" class="sort-link" title="Ordenar por fecha de reserva">
                                    <span>Fecha reserva</span>
                                    {!! \App\Helpers\SortHelper::icon('reservation_date', $sortColumns) !!}
                                </a>
                            </th>
                            {{-- Autor --}}
                            <th>
                                <a href="{{ \App\Helpers\SortHelper::url('user', $sortColumns) }}" class="sort-link" title="Ordenar por autor">
                                    <span>Autor</span>
                                    {!! \App\Helpers\SortHelper::icon('user', $sortColumns) !!}
                                </a>
                            </th>
                            {{-- Información --}}
                            <th>
                                <a href="{{ \App\Helpers\SortHelper::url('information', $sortColumns) }}" class="sort-link" title="Ordenar por información">
                                    <span>Información</span>
                                    {!! \App\Helpers\SortHelper::icon('information', $sortColumns) !!}
                                </a>
                            </th>
                            {{-- Cancelada por --}}
                            <th>
                                <a href="{{ \App\Helpers\SortHelper::url('canceled_by', $sortColumns) }}" class="sort-link" title="Ordenar por autor de la cancelación">
                                    <span>Cancelada por</span>
                                    {!! \App\Helpers\SortHelper::icon('canceled_by', $sortColumns) !!}
                                </a>
                            </th>
                            {{-- Fecha cancelación --}}
                            <th>
                                <a href="{{ \App\Helpers\SortHelper::url('cancelation_date', $sortColumns) }}" class="sort-link" title="Ordenar por autor de la fecha de la cancelación">
                                    <span>Fecha cancelación</span>
                                    {!! \App\Helpers\SortHelper::icon('cancelation_date', $sortColumns) !!}
                                </a>
                            </th>
                            {{-- Estado --}}
                            <th>
                                <a href="{{ \App\Helpers\SortHelper::url('payment_status', $sortColumns) }}" class="sort-link" title="Ordenar por autor del estado del pago">
                                    <span>Estado</span>
                                    {!! \App\Helpers\SortHelper::icon('payment_status', $sortColumns) !!}
                                </a>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cancellations as $index => $cancellation)
                        <tr>
                            <th>{{ $cancellations->firstItem() + $index }}</th>
                            <td>{{ $cancellation->court->name }}</td>
                            <td>{{ $cancellation->start_time->format('Y-m-d · H:i') }}</td>
                            <td>{{ $cancellation->user->email ?? 'Gestión' }}</td>
                            <td class="text-truncate-cell" title="{{ $cancellation->information }}">{{ $cancellation->information }}</td>
                            {{-- Cancelada por --}}
                            <td>
                                {{-- Si la reserva la canceló el gestor, añadimos la razón --}}
                                @if($cancellation->canceled_by === \App\Enums\CanceledBy::Manager) 
                                    Gestión - {{ $cancellation->cancellation_reason }}
                                @elseif($cancellation->canceled_by) 
                                    {{ $cancellation->canceled_by->label() }}
                                @else -
                                @endif
                            </td>
                            {{-- Fecha de cancelación --}}
                            <td>{{ $cancellation->canceled_at?->format('Y-m-d · H:i') ?? '-' }}</td>
                            {{-- Estado de cancelación --}}
                            <td>
                                <span class="type-badge {{ $cancellation->payment_status->badgeColor() }}">
                                    <i class="{{ $cancellation->payment_status->badgeIcon() }}" aria-hidden="true"></i>
                                    {{ $cancellation->payment_status->label() }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $cancellations->links() }}
            </div>
            @endif
        </div>
    </div>
</main>
@endsection