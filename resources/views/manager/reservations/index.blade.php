@extends('layouts.staff')

@section('title', 'Todas las reservas · Moral de Calatrava')

@section('titleHeader', 'Gestión de reservas · Moral de Calatrava')

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
                <i class="ti ti-calendar"></i>
                <div>
                    <h2>Historial de reservas</h2>
                    <small class="text-muted">Puedes filtrar las reservas con diferentes criterios</small>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-4">
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
                {{-- Estado de pago --}}
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="paid" @selected($filters['status'] === 'paid')>Pagada</option>
                        <option value="pending" @selected($filters['status'] === 'pending')>Pendiente</option>
                        <option value="canceled" @selected($filters['status'] === 'canceled')>Cancelada</option>
                        <option value="refunded" @selected($filters['status'] === 'refunded')>Reembolsada</option>
                    </select>
                </div>
                {{-- Fecha concreta --}}
                <div class="col-md-2">
                    <input type="date" name="date" value="{{ $filters['date'] }}" class="form-control">
                </div>
                {{-- Fechas pasadas / futuras --}}
                <div class="col-md-2">
                    <select name="date_range" class="form-select">
                        <option value="">
                            Todas las fechas
                        </option>

                        <option value="past" @selected($filters['date_range'] === 'past')>
                            Fechas pasadas
                        </option>

                        <option value="future" @selected($filters['date_range'] === 'future')>
                            Fechas futuras
                        </option>
                    </select>
                </div>
                {{-- Botón para filtrar --}}
                <div class="w-auto y px-4">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
            </form>

            @if($reservations->isEmpty())
                <p class="text-muted mb-0">No hay reservas que coincidan con los filtros.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
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
                                {{-- Fecha --}}
                                <th>
                                    <a href="{{ \App\Helpers\SortHelper::url('date', $sortColumns) }}" class="sort-link" title="Ordenar por fecha">
                                        <span>Fecha</span>
                                        {!! \App\Helpers\SortHelper::icon('date', $sortColumns) !!}
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
                                {{-- Estado de pago --}}
                                <th>
                                    <a href="{{ \App\Helpers\SortHelper::url('payment_status', $sortColumns) }}" class="sort-link" title="Ordenar por estado de pago">
                                        <span>Estado de pago</span>
                                        {!! \App\Helpers\SortHelper::icon('payment_status', $sortColumns) !!}
                                    </a>
                                </th>
                                <th>Editar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reservations as $index => $reservation)
                                <tr>
                                    <th>{{ $reservations->firstItem() + $index }}</th>
                                    <td>{{ $reservation->court->name }}</td>
                                    <td>{{ $reservation->start_time->format('Y-m-d') }} · {{ $reservation->start_time->format('H:i') }} - {{ $reservation->end_time->format('H:i') }}</td>
                                    <td>{{ $reservation->user->email ?? 'Gestión' }}</td>
                                    <td>{{ $reservation->information }}</td>
                                    <td>
                                        @if(empty($reservation->user_id))
                                            <span class="type-badge green"><span class="dot"></span>Pagada (Gestión)</span>
                                        @else
                                            @switch($reservation->payment_status)
                                                @case('paid')
                                                    <span class="type-badge green"><span class="dot"></span>Pagada</span>
                                                    @break;
                                                @case('pending')
                                                    <span class="type-badge blue"><span class="dot"></span>Pendiente</span>
                                                    @break
                                                @case('refunded')
                                                    <span class="type-badge purple"><span class="dot"></span>Reembolsada</span>
                                                    @break
                                                @case('canceled')
                                                    <span class="type-badge red"><span class="dot"></span>Cancelada</span>
                                                    @break
                                                @default
                                                    <span class="badge-secondary"><span class="dot"></span>{{ ucfirst($reservation->payment_status) }}</span>
                                            @endswitch
                                        @endif
                                    </td>
                                    <td>
                                        @if(in_array($reservation->payment_status, ['canceled', 'refunded']))
                                            <span class="text-muted">Reserva cancelada</span>
                                        @elseif($reservation->start_time->isFuture())
                                            <a href="{{ route('manager.reservations.edit', $reservation) }}" class="btn btn-sm btn-warning">Editar</a>
                                        @else
                                            <span class="text-muted">Fecha pasada</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $reservations->links() }}
                </div>
            @endif
            <div class="mt-2 text-start">
                <a href="{{ route('manager.reservations.create') }}" class="btn btn-success">
                    Añadir reserva
                </a>
            </div>
        </div>
        </div>
    </div>
</main>
</div>
@endsection