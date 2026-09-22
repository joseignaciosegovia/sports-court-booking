@extends('layouts.client')

@section('title', 'Reservar pista · Moral de Calatrava')

@section('titleHeader', 'Reservas · Moral de Calatrava')

@push('styles')
    @vite('resources/css/calendar.css')
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
                    <i class="ti ti-plus" aria-hidden="true"></i>
                    <div>
                        <h2>Nueva reserva</h2>
                        <small class="text-muted">Escoge una pista para reservar un horario</small>
                    </div>
                </div>

                <div class="accordion accordion-flush mt-4" id="elegirPista">
                    @forelse ($courtsByFacility as $facility => $courtsFacility)
                        @php
                            $isFirst = $loop->first;
                        @endphp
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button
                                    class="accordion-button {{ $isFirst ? '' : 'collapsed' }}"
                                    type="button" data-bs-toggle="collapse"
                                    data-bs-target="#flush-collapse{{ $loop->index }}"
                                    aria-expanded="{{ $isFirst ? 'true' : 'false' }}"
                                    aria-controls="flush-collapse{{ $loop->index }}"
                                >
                                    {{ $facility }}
                                </button>
                            </h2>

                            <div id="flush-collapse{{ $loop->index }}" class="accordion-collapse collapse {{ $isFirst ? 'show' : '' }}" data-bs-parent="#elegirPista">
                                <div class="accordion-body court-list">
                                    @forelse ($courtsFacility as $court)
                                    
                                        <a href="#" class="nav-link court-link" 
                                            data-court-id="{{ $court->id }}" 
                                            data-court-name="{{ $court->name }}" 
                                            data-court-price="{{ $court->reservation_price }}"
                                        >
                                            <span class="court-dot" aria-hidden="true"></span>
                                            {{ $court->name }}
                                        </a>
                                    @empty
                                        <p class="text-muted mb-0">No hay pistas disponibles en esta instalación</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center my-3">No hay instalaciones disponibles</p>
                    @endforelse
                </div>

                {{-- Bloque del calendario, oculto hasta que se elija una pista --}}
                <div id="calendar-section" class="mt-4 d-none">
                    <div class="seccionSubtitulo mb-3">
                        <i class="ti ti-calendar" aria-hidden="true"></i>
                        <div>
                            <h2>Horarios de la pista <span id="selected-court-name" class="fw-bold text-primary"></span></h2>
                            <small class="text-muted">Haz clic en un hueco libre para reservarlo</small>
                        </div>
                    </div>
                    {{-- Leyenda --}}
                    <div class="calendar-legend d-flex align-items-center mt-3 mb-3" role="group" aria-label="Leyenda del calendario">
                        <span class="legend-title">Leyenda:</span>
                        <span class="legend-item"><span class="legend-dot legend-disponible"></span>Libre</span>
                        <span class="legend-item"><span class="legend-dot legend-ocupada"></span>Ocupada</span>
                    </div>

                    {{-- El contenido será accesible desde un JavaScript --}}
                    <div
                        id="calendar"
                        data-opening-time="{{ $openingTime }}"
                        data-closing-time="{{ $closingTime }}"
                        data-events-url-template="{{ route('client.reservations.schedule', ['court' => '__COURT_ID__']) }}"
                    ></div>

                    <form id="reservation-form" method="POST" action="{{ route('client.reservations.store') }}" class="mt-3">
                        @csrf
                        <input type="hidden" name="court_id" id="form-court-id">
                        <input type="hidden" name="start_time" id="form-start-time">

                        <div id="selection-summary" class="alert alert-info d-none d-flex justify-content-between align-items-center">
                            <div class="selection-info">
                                <div>
                                    Horario seleccionado: <strong id="selection-text"></strong>
                                </div>
                                <div>
                                    Precio de la reserva: <strong id="selection-price"></strong>
                                </div>
                                <div>
                                    Si se cancela la reserva con menos de 12 horas de antelación, no se devolverá el dinero
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm">Confirmar reserva</button>
                        </div>
                    </form>

                    @error('start_time')
                        <div class="alert alert-danger mt-3">{{ $message }}</div>
                    @enderror
                </div>
            </div>
    </main>
@endsection

@push('scripts')
    @vite('resources/js/app.js')
    @vite('resources/js/court-reservation-calendar.js')
@endpush