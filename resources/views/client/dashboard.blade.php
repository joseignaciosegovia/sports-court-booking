@extends('layouts.client')

@section('title', 'Página principal · Moral de Calatrava')

@section('titleHeader', 'Página principal · Moral de Calatrava')

@push('styles')
    @vite('resources/css/dashboard.css')
@endpush

@section('client-content')
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
        {{-- SECCIÓN: RESERVAS --}}
        <div class="card shadow-sm border-0">
            <div class="p-3 py-4">
                <div>
                    <div class="dash-section-header">
                        <span><i class="ti ti-calendar" aria-hidden="true"></i> Reservas</span>
                        <a href="{{ route('client.reservations.index') }}">Historial de reservas <i class="ti ti-arrow-right" aria-hidden="true"></i></a>
                    </div>
                    {{-- SECCIÓN: RESERVAS --}}
                    <div class="dash-grid-2">
                        {{-- Tarjeta: reservas este mes --}}
                        <div class="dash-card dash-card-accent">
                            <div class="lbl"><i class="ti ti-calendar-stats" aria-hidden="true"></i> Reservas este mes</div>
                            <div class="val {{ $reservationsThisMonth === 0 ? 'val-zero' : '' }}">{{ $reservationsThisMonth }}</div>
                            <div class="card-sub"><span class="badge badge-green">{{ now()->translatedFormat('F Y') }}</span></div>
                        </div>
                        {{-- Tarjeta: próxima reserva --}}
                        <div class="dash-card">
                            {{-- Si hay reservas futuras --}}
                            @if($nextReservation != null)
                            <div class="lbl"><i class="ti ti-clock" aria-hidden="true"></i> Próxima reserva</div>
                            <div class="next-card">
                                <div class="next-icon">
                                    <i class="ti ti-soccer-field" aria-hidden="true"></i>
                                </div>
                                <div class="next-info">
                                    <div class="val-md">{{ "$nextReservationCourt->name" }}</div>
                                    <div class="pill-row">
                                        <span class="badge badge-blue"><i class="ti ti-clock" aria-hidden="true"></i>{{ $nextReservation->start_time }}</span>
                                        <span class="badge badge-amber"><i class="ti ti-map-pin" aria-hidden="true"></i>{{ $nextReservationCourt->location }}</span>
                                    </div>
                                </div>
                            </div>
                            {{-- Si no hay reservas futuras --}}
                            @else
                            <div class="lbl"><i class="ti ti-clock" aria-hidden="true"></i> No tienes reservas en fechas futuras</div>
                            <div class="next-card">
                                <div class="next-info">
                                    <div class="card-sub">
                                        <i class="ti ti-calendar-event" aria-hidden="true"></i>
                                        Cuando tengas reservas futuras podrás ver sus datos aquí
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                <hr class="mt-0 mb-4" style="border-color: #dee2e6;">
                {{-- SECCIÓN: PISTAS E INSTALACIONES --}}
                <div>
                    <div class="dash-section-header">
                        <span><i class="ti ti-soccer-field" aria-hidden="true"></i> Pistas e instalaciones</span>
                        <a href="{{ route('client.reservations.create') }}">Nueva reserva <i class="ti ti-arrow-right" aria-hidden="true"></i></a>
                    </div>
                    <div class="dash-grid-2">
                        {{-- Tarjeta: pistas disponibles --}}
                        <div class="dash-card dash-card-accent dash-card-accent-blue">
                            <div class="lbl"><i class="ti ti-soccer-field" aria-hidden="true"></i> Pistas disponibles</div>
                            <div class="val {{ $courtsCount === 0 ? 'val-zero' : '' }}">{{ $courtsCount }}</div>
                        </div>
                        {{-- Tarjeta: datos de instalaciones --}}
                        <div class="dash-card">
                            <div class="info-row">
                                <span class="info-lbl"><i class="ti ti-building" aria-hidden="true"></i> Instalaciones disponibles</span>
                                <span class="info-val {{ $locationsCount === 0 ? 'val-zero' : '' }}">{{ $locationsCount }}</span>
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
                {{-- SECCIÓN: COMENTARIOS --}}
                <div>
                    <div class="dash-section-header">
                        <span><i class="ti ti-mail" aria-hidden="true"></i> Comentarios</span>
                        <a href="{{ route('client.feedback.index') }}">Historial de comentarios <i class="ti ti-arrow-right" aria-hidden="true"></i></a>
                    </div>
                    <div class="dash-grid-2">
                        <div class="dash-card dash-card-accent dash-card-accent-amber">
                            <div class="lbl"><i class="ti ti-bulb" aria-hidden="true"></i> Sugerencias enviadas</div>
                            <div class="dash-card-footer">
                                <div class="val {{ $suggestionsCount  === 0 ? 'val-zero' : '' }}">{{ $suggestionsCount  }}</div>
                                <a href="{{ route('client.feedback.index', ['type' => App\Enums\FeedbackType::Suggestion]) }}" class="dash-card-link">Ver →</a>
                            </div>
                        </div>
                        <div class="dash-card">
                            <div class="lbl"><i class="ti ti-alert-triangle" aria-hidden="true"></i> Incidencias enviadas</div>
                            <div class="dash-card-footer">
                                <div class="val {{ $incidentsCount  === 0 ? 'val-zero' : '' }}">{{ $incidentsCount  }}</div>
                                <a href="{{ route('client.feedback.index', ['type' => App\Enums\FeedbackType::Incident]) }}" class="dash-card-link">Ver →</a>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="mt-0 mb-4" style="border-color: #dee2e6;">
                
            </div>
        </div>
    </main> 
    </div>
@endsection