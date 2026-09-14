@extends('layouts.staff')

@section('title', 'Panel de administración · Moral de Calatrava')

@section('titleHeader', 'Panel de administración · Moral de Calatrava')

@push('scriptsCabecera')
    @vite('resources/css/dashboard.css')
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

    {{-- SECCIÓN TARJETAS --}}
    <div class="card shadow-sm border-0">
        <div class="p-3 py-4">
            @if($authUser->role === 'admin')
            {{-- Tarjetas de usuarios por rol --}}
            <div class="dash-section-header">
                <span><i class="ti ti-user" aria-hidden="true"></i> Roles</span>
                <a href="{{ route('admin.managers.index') }}">Administrar gestores <i class="ti ti-arrow-right" aria-hidden="true"></i></a>
            </div>
            <div class="dash-grid-3">
                <div class="dash-card dash-card-accent">
                    <div class="lbl"><i class="ti ti-user" aria-hidden="true"></i> Clientes</div>
                    <div class="val {{ $clientsCount === 0 ? 'val-zero' : '' }}">{{ $clientsCount }}</div>
                </div>
                <div class="dash-card">
                    <div class="lbl"><i class="ti ti-user-cog" aria-hidden="true"></i> Gestores</div>
                    <div class="val {{ $managersCount === 0 ? 'val-zero' : '' }}">{{ $managersCount }}</div>
                </div>
                <div class="dash-card">
                    <div class="lbl"><i class="ti ti-user-cog" aria-hidden="true"></i> Administradores</div>
                    <div class="val {{ $adminsCount === 0 ? 'val-zero' : '' }}">{{ $adminsCount }}</div>
                </div>
            </div>
            <hr class="mt-0 mb-4" style="border-color: #dee2e6;">
            @endif
            {{-- Tarjetas de Reservas --}}
            <div>
                <div class="dash-section-header">
                    <span><i class="ti ti-calendar" aria-hidden="true"></i> Reservas</span>
                    <a href="{{ route('manager.reservations.index') }}">Ver todas <i class="ti ti-arrow-right" aria-hidden="true"></i></a>
                </div>
                <div class="dash-grid-2">
                    <div class="dash-card dash-card-accent dash-card-accent-amber">
                        <div class="lbl"><i class="ti ti-calendar-stats" aria-hidden="true"></i> Reservas de hoy</div>
                        <div class="dash-card-footer">
                            <div class="val {{ $todayReservationsCount === 0 ? 'val-zero' : '' }}">{{ $todayReservationsCount }}</div>
                            <a href="{{ route('manager.reservations.index', ['date' => now()->toDateString()]) }}" class="dash-card-link">Ver →</a>
                        </div>
                    </div>
                    <div class="dash-card">
                        <div class="lbl"><i class="ti ti-calendar-x" aria-hidden="true"></i> Reservas canceladas en total</div>
                        <div class="dash-card-footer">
                            <div class="val {{ $canceledReservationsCount  === 0 ? 'val-zero' : '' }}">{{ $canceledReservationsCount  }}</div>
                            <a href="{{ route('manager.reservations.cancellations') }}" class="dash-card-link">Ver →</a>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="mt-0 mb-4" style="border-color: #dee2e6;">
            {{-- Tarjetas de pistas e instalaciones --}}
            <div>
                <div class="dash-section-header">
                    <span><i class="ti ti-soccer-field" aria-hidden="true"></i> Pistas e instalaciones</span>
                    <a href="{{ route('manager.courts.index') }}">Administrar pistas <i class="ti ti-arrow-right" aria-hidden="true"></i></a>
                </div>
                <div class="dash-grid-2">
                    <div class="dash-card dash-card-accent dash-card-accent-blue">
                        <div class="lbl"><i class="ti ti-soccer-field" aria-hidden="true"></i> Pistas disponibles</div>
                        <div class="val {{ $courtsCount  === 0 ? 'val-zero' : '' }}">{{ $courtsCount }}</div>
                    </div>
                    <div class="dash-card">
                        <div class="info-row" style="grid-column: span 2;">
                            <span class="info-lbl"><i class="ti ti-building" aria-hidden="true"></i> Instalaciones disponibles</span>
                            <span class="info-val {{ $facilitiesCount  === 0 ? 'val-zero' : '' }}">{{ $facilitiesCount  }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-lbl"><i class="ti ti-door-enter" aria-hidden="true"></i> Hora de apertura</span>
                            <span class="info-val">{{ $openingTime }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-lbl"><i class="ti ti-door-exit" aria-hidden="true"></i> Hora de cierre</span>
                            <span class="info-val">{{ $closingTime }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="mt-0 mb-4" style="border-color: #dee2e6;">
            {{-- Tarjetas de sugerencias e incidencias --}}
            <div>
                <div class="dash-section-header">
                    <span><i class="ti ti-mail" aria-hidden="true"></i> Sugerencias e incidencias</span>
                    <a href="{{ route('manager.suggestions.index') }}">Ver todas <i class="ti ti-arrow-right" aria-hidden="true"></i></a>
                </div>
                <div class="dash-grid-2">
                    <div class="dash-card dash-card-accent dash-card-accent-pink">
                        <div class="lbl"><i class="ti ti-mail" aria-hidden="true"></i> Sugerencias totales</div>
                        <div class="val {{ $suggestionsCount === 0 ? 'val-zero' : '' }}">{{ $suggestionsCount }}</div>
                    </div>
                    <div class="dash-card">
                        <div class="lbl"><i class="ti ti-mail" aria-hidden="true"></i> Sugerencias este mes</div>
                        <div class="val {{ $suggestionsThisMonth === 0 ? 'val-zero' : '' }}">{{ $suggestionsThisMonth }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
</div>
@endsection