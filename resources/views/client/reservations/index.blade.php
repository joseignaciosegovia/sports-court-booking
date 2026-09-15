@extends('layouts.client')

@section('title', 'Historial de reservas · Moral de Calatrava')

@section('titleHeader', 'Reservas · Moral de Calatrava')

@push('styles')
    @vite([
        'resources/css/table.css',
        'resources/js/countdown-timer.js'
    ])
@endpush

@section('client-content')
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
                <div class="seccionSubtitulo">
                    <i class="ti ti-calendar" aria-hidden="true"></i>
                    <div>
                        <h2>Historial de reservas</h2>
                        <small class="text-muted">Reservas realizadas anteriormente</small>
                    </div>
                </div>
                <hr class="mt-0 mb-4" style="border-color: #dee2e6;">
                {{-- Filtros --}}
                <form method="GET" class="row g-2 mb-4">
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
                    <table class="table table-striped table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>Número</th>
                                {{-- Pista --}}
                                <th>
                                    <a href="{{ \App\Helpers\SortHelper::url('court', $sortColumns) }}" class="sort-link" title="Ordenar por pista">
                                        <span>Pista</span>
                                        {!! \App\Helpers\SortHelper::icon('court', $sortColumns) !!}
                                    </a>
                                </th>
                                {{-- Fecha --}}
                                <th>
                                    <a href="{{ \App\Helpers\SortHelper::url('start_time', $sortColumns) }}" class="sort-link" title="Ordenar por fecha y hora">
                                        <span>Fecha</span>
                                        {!! \App\Helpers\SortHelper::icon('start_time', $sortColumns) !!}
                                    </a>
                                </th>
                                {{-- Precio --}}
                                <th>
                                    <a href="{{ \App\Helpers\SortHelper::url('price', $sortColumns) }}" class="sort-link" title="Ordenar por precio">
                                        <span>Precio</span>
                                        {!! \App\Helpers\SortHelper::icon('price', $sortColumns) !!}
                                    </a>
                                </th>
                                {{-- Estado de pago --}}
                                <th>
                                    <a href="{{ \App\Helpers\SortHelper::url('status', $sortColumns) }}" class="sort-link" title="Ordenar por estado">
                                        <span>Estado de pago</span>
                                        {!! \App\Helpers\SortHelper::icon('status', $sortColumns) !!}
                                    </a>
                                </th>
                                <th>Editar reserva</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Recorremos las reservas --}}
                            @foreach($reservations as $index => $reservation)
                            <tr>
                                <th>{{ $reservations->firstItem() + $index }}</th>
                                <td>{{ $reservation->court->name }}</td>
                                <td>{{ $reservation->start_time->format('Y-m-d') }} · {{ $reservation->start_time->format('H:i') }} - {{ $reservation->end_time->format('H:i') }}</td>
                                <td>{{ $reservation->court->reservation_price }}</td>
                                <td>
                                    @switch($reservation->payment_status)
                                        @case('paid')
                                            <span class="type-badge green"><span class="dot"></span>Pagada</span>
                                            @break;
                                        @case('pending')
                                            <span class="type-badge blue"><span class="dot"></span>Pendiente</span>
                                            @if($reservation->expires_at)
                                                <div class="countdown-timer" data-expires="{{ $reservation->expires_at->toIso8601String() }}">
                                                    <i class="ti ti-clock" aria-hidden="true"></i>
                                                    <span class="countdown-text">Calculando...</span>
                                                </div>
                                            @endif
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
                                </td>
                                <td>
                                    {{-- Si la fecha de la reserva no se ha pasado y la reserva se pagó o está pendiente de pagarse, permitimos que se pueda cancelar --}}
                                    @if(in_array($reservation->payment_status, ['paid', 'pending']) && $reservation->start_time->isFuture())
                                        <div class="d-flex gap-2">
                                            {{-- Si la reserva está en estado pendiente y no ha expirado, se permite continuar el pago --}}
                                            @if($reservation->payment_status === 'pending' && $reservation->expires_at && $reservation->expires_at->isFuture())
                                                <a href="{{ route('client.reservations.payment.resume', $reservation) }}" class="btn btn-success">
                                                    Continuar pago
                                                </a>
                                            @endif
                                            <form method="POST" action="{{ route('client.reservations.cancel', $reservation) }}" onsubmit="return confirm('¿Seguro que quieres cancelar esta reserva? Si faltan menos de 12 horas, no habrá devolución.');" style="display: inline;">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-danger">Cancelar</button>
                                            </form>
                                        </div>
                                    @elseif(in_array($reservation->payment_status, ['canceled', 'refunded']))
                                        @switch($reservation->canceled_by)
                                            @case('client')
                                                <span class="text-muted">Cancelada por el usuario</span>
                                                @break;
                                            @case('manager')
                                                <span class="text-muted">Cancelada por la gestión</span>
                                                @break
                                            @case('system')
                                                <span class="text-muted">No se pagó a tiempo</span>
                                                @break
                                            @default
                                        @endswitch
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
        </div>
        </div>
    </main>
@endsection
