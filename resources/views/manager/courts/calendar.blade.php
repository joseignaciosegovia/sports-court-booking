@extends('layouts.staff')

@section('title', 'Calendario · Moral de Calatrava')

@section('titleHeader', 'Gestión de pistas · Moral de Calatrava')

@push('styles')
    @vite('resources/css/calendar.css')
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
                <i class="ti ti-soccer-field"></i>
                <div>
                    <h2>Calendario de la pista {{ $court->name }}</h2>
                    <small class="text-muted">Consulta los horarios reservados de la pista {{ $court->name }}</small>
                </div>
            </div>
            <br>
            <div id="calendar"
                data-court-id="{{ $court->id }}"
                data-opening-time="{{ $openingTime }}"
                data-closing-time="{{ $closingTime }}"
                data-events-url="{{ route('manager.courts.events', $court) }}"
                data-reservation-info-url-template="{{ route('manager.reservations.show', ['reservation' => 'RESERVATION_ID']) }}"
                data-reservation-reschedule-url-template="{{ route('manager.reservations.reschedule', ['reservation' => 'RESERVATION_ID']) }}"
                data-reservation-update-url-template="{{ route('manager.reservations.update', ['reservation' => 'RESERVATION_ID']) }}"
                data-quick-create-url="{{ route('manager.reservations.quick.store', $court) }}">
            </div>
        </div>
    </div>
    <div class="mt-2 text-start">
        <a href="{{ route('manager.courts.index') }}" class="btn btn-secondary">Volver atrás</a>
    </div>
</main>

{{-- Modal de información / edición de reserva --}}
<div class="modal fade" id="reservationInfoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Información de la reserva</h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                </button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="modal-reservation-id">

                {{-- Pista --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">Pista</label>

                    <input type="text"
                           id="modal-court-name"
                           class="form-control"
                           disabled>
                </div>

                {{-- Cliente --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">Cliente</label>
                    <input type="text"
                           id="modal-client"
                           class="form-control"
                           disabled>
                </div>

                {{-- Fecha --}}
                <div class="mb-3">
                    <label for="modal-date"
                           class="form-label fw-bold">
                        Fecha
                    </label>
                    <input type="date"
                           id="modal-date"
                           class="form-control"
                           disabled>
                </div>

                {{-- Hora inicio --}}
                <div class="mb-3">
                    <label for="modal-start-time"
                           class="form-label fw-bold">
                        Hora de inicio
                    </label>
                    <select id="modal-start-time"
                           class="form-select"
                           disabled>
                    </select>
                </div>

                {{-- Hora fin --}}
                <div class="mb-3">
                    <label for="modal-end-time"
                           class="form-label fw-bold">
                        Hora de fin
                    </label>
                    <select id="modal-end-time"
                           class="form-select"
                           disabled>
                    </select>
                </div>

                {{-- Información --}}
                <div class="mb-3">
                    <label for="modal-information"
                           class="form-label fw-bold">
                        Información
                    </label>
                    <textarea id="modal-information"
                              class="form-control"
                              rows="3"
                              disabled></textarea>
                </div>

                {{-- Estado --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Estado
                    </label>
                    <input type="text"
                           id="modal-status"
                           class="form-control"
                           disabled>
                </div>

                {{-- Error --}}
                <div id="modal-error"
                     class="alert alert-danger d-none">
                </div>

            </div>

            <div class="modal-footer">
                <button type="button"
                        id="modal-edit-btn"
                        class="btn btn-primary">
                    <i class="ti ti-edit"></i>
                    Editar
                </button>
                <button type="button"
                        id="modal-cancel-edit-btn"
                        class="btn btn-secondary d-none">
                    Cancelar
                </button>
                <button type="button"
                        id="modal-save-btn"
                        class="btn btn-success d-none">
                    <i class="ti ti-device-floppy"></i>
                    Guardar cambios
                </button>
                <button type="button"
                        class="btn btn-outline-secondary"
                        data-bs-dismiss="modal">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal para crear reserva rápida --}}
<div class="modal fade" id="quickCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva reserva</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="quick-create-time-range" class="text-muted"></p>
                <label for="quick-create-information" class="form-label">Información</label>
                <input type="text" id="quick-create-information" class="form-control" placeholder="Ej.: Partido de baloncesto">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="quick-create-confirm" class="btn btn-success">Crear reserva</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal de confirmación de acción (reprogramar / guardar cambios) --}}
<div class="modal fade" id="actionSuccessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                    ¡Operación completada!
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="action-success-message" class="mb-0"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                    Aceptar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/locales/es.global.min.js"></script>
    @vite('resources/js/calendar-manager.js')
@endpush