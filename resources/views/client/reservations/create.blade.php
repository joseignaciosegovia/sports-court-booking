@extends('layouts.client')

@section('title', 'Reservar pista · Moral de Calatrava')

@section('titleHeader', 'Reservas · Moral de Calatrava')

@push('scriptsCabecera')
    @vite('resources/css/calendar.css')
    @vite(['resources/js/app.js'])
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

                <div class="accordion accordion-flush" id="elegirPista">
                    @forelse ($courtsByFacility as $facility => $courtsFacility)
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button
                                    class="accordion-button collapsed"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#flush-collapse{{ $loop->index }}"
                                    aria-expanded="false"
                                    aria-controls="flush-collapse{{ $loop->index }}"
                                >
                                    {{ $facility }}
                                </button>
                            </h2>

                            <div id="flush-collapse{{ $loop->index }}" class="accordion-collapse collapse" data-bs-parent="#elegirPista">
                                @forelse ($courtsFacility as $court)
                                    <div class="accordion-body">
                                        <a href="#" class="nav-link ms-3 my-1 court-link" data-court-id="{{ $court->id }}" data-court-name="{{ $court->name }}" data-court-price="{{ $court->reservation_price }}">
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

                    <div id="calendar"></div>

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

@push('scriptsPie')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const calendarSection = document.getElementById('calendar-section');
            const calendarEl = document.getElementById('calendar');
            const selectedCourtName = document.getElementById('selected-court-name');
            const summaryBox = document.getElementById('selection-summary');
            const summaryText = document.getElementById('selection-text');
            const summaryPrice = document.getElementById('selection-price');
            const formCourtId = document.getElementById('form-court-id');
            const formStartTime = document.getElementById('form-start-time');

            let calendar = null;

            function initCalendar(courtId, courtPrice) {
                if (calendar) {
                    calendar.destroy();
                }

                calendar = new FullCalendar.Calendar(calendarEl, {
                    plugins: [
                        FullCalendar.timeGridPlugin,
                        FullCalendar.dayGridPlugin,
                        FullCalendar.interactionPlugin,
                    ],
                    initialView: 'timeGridWeek',
                    locale: FullCalendar.esLocale,
                    initialView: 'timeGridWeek',
                    slotMinTime: '{{ $openingTime }}:00',
                    slotMaxTime: '{{ $closingTime }}:00',
                    slotDuration: '01:00:00',
                    hiddenDays: [6, 0],
                    height: 'auto',
                    allDaySlot: false,
                    selectable: true,
                    selectOverlap: false,
                    events: `/reservas/horarios/${courtId}`,

                    // Formato de la columna que indica la hora
                    slotLabelFormat:{
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true,
                        meridiem: 'short',
                    },

                    headerToolbar: {
                        left: "prev,next,today",
                        center: "title",
                        right: "timeGridWeek,timeGridDay"
                    }, 

                    // Pinchamos en una franja del calendario
                    select: function (info) {
                        const now = new Date();

                        if (info.start < now) {
                            calendar.unselect(); // quita el resaltado azul de la selección inválida
                            summaryBox.classList.add('d-none');
                            return; // no seguimos, no se rellena el formulario
                        }
                        formCourtId.value = courtId;
                        formStartTime.value = info.startStr;

                        summaryText.textContent = info.start.toLocaleString('es-ES', {
                            dateStyle: 'medium',
                            timeStyle: 'short',
                        });

                        summaryPrice.textContent = new Intl.NumberFormat('es-ES', {
                            style: 'currency',
                            currency: 'EUR',
                        }).format(courtPrice);
                        
                        summaryBox.classList.remove('d-none');
                    },
                });

                calendar.render();

                window.debugCalendar = calendar;
            }

            document.querySelectorAll('.court-link').forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();

                    const courtId = this.dataset.courtId;
                    const courtName = this.dataset.courtName;
                    const courtPrice = this.dataset.courtPrice;

                    // resalta visualmente la pista elegida
                    document.querySelectorAll('.court-link').forEach(l => l.classList.remove('fw-bold', 'text-primary'));
                    this.classList.add('fw-bold', 'text-primary');

                    selectedCourtName.textContent = courtName;
                    summaryBox.classList.add('d-none');
                    calendarSection.classList.remove('d-none');

                    initCalendar(courtId, courtPrice);

                    // scroll suave hasta el calendario, útil sobre todo en móvil
                    calendarSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });
        });
    </script>
@endpush