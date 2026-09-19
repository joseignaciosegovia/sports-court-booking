@php
    use App\Enums\PaymentStatus;
@endphp

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
                <x-filters.filter-bar :action="route('client.reservations.index')" :active-filters="$filters">
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
                            <option value="{{ PaymentStatus::Paid->value }}" @selected($filters['status'] === PaymentStatus::Paid->value)>{{ PaymentStatus::Paid->label() }}</option>
                            <option value="{{ PaymentStatus::Pending->value }}" @selected($filters['status'] === PaymentStatus::Pending->value)>{{ PaymentStatus::Pending->label() }}</option>
                            <option value="{{ PaymentStatus::Canceled->value }}" @selected($filters['status'] === PaymentStatus::Canceled->value)>{{ PaymentStatus::Canceled->label() }}</option>
                            <option value="{{ PaymentStatus::Refunded->value }}" @selected($filters['status'] === PaymentStatus::Refunded->value)>{{ PaymentStatus::Refunded->label() }}</option>
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
                    {{-- CHIPS DE FILTROS --}}
                    <x-slot:chips>
                        {{-- Chip de pista --}}
                        @if(!empty($filters['court_id']))
                            @php
                                $selectedCourt = $courts->find($filters['court_id']);
                            @endphp

                            @if($selectedCourt)
                                <x-filters.filter-chip
                                    :label="'Pista: ' . $selectedCourt->name"
                                    :remove-url="request()->fullUrlWithoutQuery('court_id')"
                                />
                            @endif
                        @endif
                    
                        {{-- Chip de estado de pago --}}
                        @if(!empty($filters['status']))
                            @php
                                $status = PaymentStatus::tryFrom($filters['status']);
                            @endphp

                            @if($status)
                                <x-filters.filter-chip
                                    :label="'Estado: ' . $status->label()"
                                    :remove-url="request()->fullUrlWithoutQuery('status')"
                                />
                            @endif
                        @endif

                        {{-- Chip de fecha concreta --}}
                        @if(!empty($filters['date']))
                            <x-filters.filter-chip
                                :label="'Fecha: ' . \Carbon\Carbon::parse($filters['date'])->format('Y-m-d')"
                                :remove-url="request()->fullUrlWithoutQuery('date')"
                            />
                        @endif

                        {{-- Chip de rango de fechas --}}
                        @if(!empty($filters['date_range']))
                            @php
                                $dateRangeLabels = [
                                    'past' => 'Fechas pasadas',
                                    'future' => 'Fechas futuras',
                                ];
                            @endphp

                            @if(isset($dateRangeLabels[$filters['date_range']]))
                                <x-filters.filter-chip
                                    :label="$dateRangeLabels[$filters['date_range']]"
                                    :remove-url="request()->fullUrlWithoutQuery('date_range')"
                                />
                            @endif
                        @endif
                    </x-slot:chips>
                </x-filters.filter-bar>

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
                                {{-- Pista --}}
                                <td>{{ $reservation->court->name }}</td>
                                {{-- Fecha --}}
                                <td>{{ $reservation->start_time->format('Y-m-d') }} · {{ $reservation->start_time->format('H:i') }} - {{ $reservation->end_time->format('H:i') }}</td>
                                {{-- Precio --}}
                                <td>{{ $reservation->court->reservation_price }}</td>
                                {{-- Estado de pago --}}
                                <td>
                                    <span class="type-badge {{ $reservation->payment_status->badgeColor() }}">
                                        <i class="{{ $reservation->payment_status->badgeIcon() }}"></i>
                                        {{ $reservation->payment_status->label() }}
                                    </span>
                                    @if($reservation->payment_status === PaymentStatus::Pending)
                                        @if($reservation->expires_at)
                                            <div class="countdown-timer" data-expires="{{ $reservation->expires_at->toIso8601String() }}">
                                                <span class="countdown-text">Calculando...</span>
                                            </div>
                                        @endif
                                    @endif
                                </td>
                                {{-- Acciones --}}
                                <td>
                                    {{-- Si la fecha de la reserva no se ha pasado y la reserva se pagó o está pendiente de pagarse, permitimos que se pueda cancelar --}}
                                    @if(in_array($reservation->payment_status, [PaymentStatus::Paid, PaymentStatus::Pending,], true) && $reservation->start_time->isFuture())
                                        <div class="d-flex gap-2">
                                            {{-- Si la reserva está en estado pendiente y no ha expirado, se permite continuar el pago --}}
                                            {{-- Continuar pago --}}
                                            @if($reservation->payment_status->isPayable() && $reservation->expires_at && $reservation->expires_at->isFuture())
                                                <a href="{{ route('client.reservations.payment.resume', $reservation) }}" class="btn btn-success">
                                                    Continuar pago
                                                </a>
                                            @endif
                                            {{-- Cancelar reserva --}}
                                            <form method="POST" action="{{ route('client.reservations.cancel', $reservation) }}" onsubmit="return confirm('¿Seguro que quieres cancelar esta reserva? Si faltan menos de 12 horas, no habrá devolución.');" style="display: inline;">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-danger">Cancelar</button>
                                            </form>
                                        </div>
                                    @elseif(in_array($reservation->payment_status, [PaymentStatus::Canceled, PaymentStatus::Refunded,], true))
                                        @if($reservation->canceled_by)
                                            <span class="text-muted">
                                                {{ $reservation->canceled_by->label() }}
                                            </span>
                                        @endif
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
