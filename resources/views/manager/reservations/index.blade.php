@php
    use App\Enums\PaymentStatus;
@endphp

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
            {{-- Filtros --}}
            <x-filters.filter-bar :action="route('manager.reservations.index')" :active-filters="$filters">
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
                        <option value="{{ PaymentStatus::Paid->value }}" @selected($filters['status'] === PaymentStatus::Paid->value)>
                            {{ PaymentStatus::Paid->label() }}
                        </option>
                        <option value="{{ PaymentStatus::Pending->value }}" @selected($filters['status'] === PaymentStatus::Pending->value)>
                            {{ PaymentStatus::Pending->label() }}
                        </option>
                        <option value="{{ PaymentStatus::Canceled->value }}" @selected($filters['status'] === PaymentStatus::Canceled->value)>
                            {{ PaymentStatus::Canceled->label() }}
                        </option>
                        <option value="{{ PaymentStatus::Refunded->value }}" @selected($filters['status'] === PaymentStatus::Refunded->value)>
                            {{ PaymentStatus::Refunded->label() }}
                        </option>
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
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reservations as $index => $reservation)
                                <tr>
                                    <th>{{ $reservations->firstItem() + $index }}</th>
                                    <td>{{ $reservation->court->name }}</td>
                                    <td>{{ $reservation->start_time->format('Y-m-d · H:i') }} - {{ $reservation->end_time->format('H:i') }}</td>
                                    <td>{{ $reservation->user->email ?? 'Gestión' }}</td>
                                    <td>{{ $reservation->information }}</td>
                                    <td>
                                        @if(empty($reservation->user_id))
                                            <span class="type-badge {{ $reservation->payment_status->badgeColor() }}">
                                                <i class="{{ $reservation->payment_status->badgeIcon() }}" aria-hidden="true"></i>
                                                {{ $reservation->payment_status->label() }} (Gestión)
                                            </span>
                                        @else
                                            <span class="type-badge {{ $reservation->payment_status->badgeColor() }}">
                                                <i class="{{ $reservation->payment_status->badgeIcon() }}" aria-hidden="true"></i>
                                                {{ $reservation->payment_status->label() }}
                                            </span>
                                        @endif
                                    </td>
                                    {{-- Acciones --}}
                                    <td class="text-center">
                                        @if($reservation->payment_status->isCanceled())
                                            <span class="text-muted">Reserva cancelada</span>
                                        @elseif($reservation->start_time->isFuture())
                                            {{-- Editar reserva --}}
                                            <a
                                                href="{{ route('manager.reservations.edit', $reservation) }}"
                                                class="btn btn-icon btn-outline-secondary btn-sm"
                                                data-bs-toggle="tooltip"
                                                title="Editar reserva"
                                                aria-label="Editar {{ $reservation->name }}"
                                            >
                                                <i class="ti ti-edit" aria-hidden="true"></i>
                                            </a>
                                            {{-- Eliminar reserva --}}
                                            <button
                                                type="button"
                                                class="btn btn-icon btn-outline-danger btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#cancelReservationModal-{{ $reservation->id }}"
                                                title="Cancelar reserva"
                                                aria-label="Cancelar {{ $reservation->name }}"
                                            >
                                                <i class="ti ti-trash" aria-hidden="true"></i>
                                            </button>
                                        @else
                                            <span class="text-muted">Fecha pasada</span>
                                        @endif
                                    </td>
                                </tr>
                                {{-- Modal para la cancelación de una reserva --}}
                                <div class="modal fade" id="cancelReservationModal-{{ $reservation->id }}" tabindex="-1"
                                    aria-labelledby="cancelReservationLabel-{{ $reservation->id }}" aria-hidden="true">

                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="cancelReservationLabel-{{ $reservation->id }}">
                                                    Cancelar reserva
                                                </h5>

                                                <button type="button"
                                                    class="btn-close"
                                                    data-bs-dismiss="modal"
                                                    aria-label="Cerrar"></button>
                                            </div>

                                            <form method="POST" action="{{ route('manager.reservations.cancel', $reservation) }}">
                                                @csrf
                                                @method('PATCH')

                                                <div class="modal-body">
                                                    <p>¿Seguro que quieres cancelar esta reserva?</p>

                                                    <p class="text-muted">
                                                        {{ $reservation->court->name }} ·
                                                        {{ $reservation->start_time->format('Y-m-d · H:i') }}
                                                    </p>

                                                    <div>
                                                        <label for="reason-{{ $reservation->id }}" class="form-label">
                                                            Motivo de la cancelación
                                                        </label>
                                                        <input
                                                            type="text"
                                                            name="reason"
                                                            id="reason-{{ $reservation->id }}"
                                                            class="form-control"
                                                            placeholder="Ej.: Avería en la instalación"
                                                            required
                                                            maxlength="255"
                                                        >
                                                    </div>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button"
                                                        class="btn btn-secondary"
                                                        data-bs-dismiss="modal">
                                                        Volver
                                                    </button>

                                                    <button type="submit" class="btn btn-danger">
                                                        Cancelar reserva
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
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