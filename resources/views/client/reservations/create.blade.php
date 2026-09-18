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

                <div class="accordion accordion-flush py-3" id="elegirPista">
                    @forelse ($courtsByFacility as $facility => $courtsFacility)
                        @php
                            $isFirst = $loop->first;
                        @endphp
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button
                                    class="accordion-button collapsed {{ $isFirst ? '' : 'collapsed' }}"
                                    type="button" data-bs-toggle="collapse"
                                    data-bs-target="#flush-collapse{{ $loop->index }}"
                                    aria-expanded="{{ $isFirst ? 'true' : 'false' }}"
                                    aria-controls="flush-collapse{{ $loop->index }}"
                                >
                                    {{ $facility }}
                                </button>
                            </h2>

                            <div id="flush-collapse{{ $loop->index }}" class="accordion-collapse collapse {{ $isFirst ? 'show' : '' }}" data-bs-parent="#elegirPista">
                                @forelse ($courtsFacility as $court)
                                    <div class="accordion-body">
                                        <a href="#" class="nav-link court-link ms-3 my-1 d-flex align-items-center gap-2" 
                                            data-court-id="{{ $court->id }}" 
                                            data-court-name="{{ $court->name }}" data-court-price="{{ $court->reservation_price }}"
                                        >
                                            <span class="court-dot" aria-hidden="true"></span>
                                            {{ $court->name }}
                                        </a>
                                    </div>
                                @empty
                                    <div class="accordion-body">
                                        <p class="text-muted mb-0">No hay pistas disponibles en esta instalación</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">No hay instalaciones disponibles</p>
                    @endforelse
                </div>

                {{-- Bloque del calendario, oculto hasta que se elija una pista --}}
                <div id="calendar-section" class="mt-4 d-none">
                    <div class="seccionSubtitulo mb-3">
                        <i class="ti ti-calendar" aria-hidden="true"></i>
                        <div>
                            <h2>Horarios de la pista <span id="selected-court-name"></span></h2>
                            <small class="text-muted">Haz clic en un hueco libre para reservarlo</small>
                        </div>
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
                            <span>Horario seleccionado: <strong id="selection-text"></strong> Precio de la reserva: <strong id="selection-price"></strong></span>
                            <button type="submit" class="btn btn-success btn-sm">Confirmar reserva</button>
                        </div>
                    </form>

                    @error('start_time')
                        <div class="alert alert-danger mt-3">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
        
    </main>
@endsection

@push('scripts')
    @vite('resources/js/app.js')
    @vite('resources/js/court-reservation-calendar.js')
@endpush