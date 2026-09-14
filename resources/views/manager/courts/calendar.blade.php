@extends('layouts.staff')

@section('title', 'Calendario · Moral de Calatrava')

@section('titleHeader', 'Gestión de pistas · Moral de Calatrava')

@push('scriptsCabecera')
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
            <div id="calendar"></div>
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
@endsection

@push('scriptsPie')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const courtId = {{ $court->id }};

        // ---------------------------------------------------------
        // MODALES
        // ---------------------------------------------------------

        const infoModalEl =
            document.getElementById('reservationInfoModal');

        const infoModal =
            new bootstrap.Modal(infoModalEl);

        const createModalEl =
            document.getElementById('quickCreateModal');

        const createModal =
            new bootstrap.Modal(createModalEl);
        
        const csrfToken = 
            document.querySelector('meta[name="csrf-token"]').content;


        // ---------------------------------------------------------
        // VARIABLES
        // ---------------------------------------------------------

        let pendingSelection = null;

        let originalReservationData = null;


        // ---------------------------------------------------------
        // CALENDARIO
        // ---------------------------------------------------------

        const calendar = new FullCalendar.Calendar(
            document.getElementById('calendar'),
            {
                initialView: 'timeGridWeek',
                locale: 'es',
                slotMinTime: '{{ $openingTime }}:00',
                slotMaxTime: '{{ $closingTime }}:00',
                slotDuration: '01:00:00',
                height: 'auto',
                allDaySlot: false,
                selectable: true,
                selectOverlap: false,
                eventOverlap: false,
                editable: true,
                eventStartEditable: true,
                eventDurationEditable: false,

                events:
                    '{{ route('manager.courts.events', $court) }}',

                slotLabelFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true,
                    meridiem: 'short',
                },

                headerToolbar: {
                    left: 'prev,next,today',
                    center: 'title',
                    right: 'timeGridWeek,timeGridDay',
                },


                // -------------------------------------------------
                // CLICK EN UNA RESERVA
                // -------------------------------------------------

                eventClick: function (info) {

                    const reservationId = info.event.id;

                    fetch(
                        `/gestion/reservations/${reservationId}/info`
                    )
                    .then(res => {

                        if (!res.ok) {
                            throw new Error(
                                'No se pudo obtener la información de la reserva.'
                            );
                        }

                        return res.json();
                    })
                    .then(data => {

                        // ID
                        document.getElementById(
                            'modal-reservation-id'
                        ).value = reservationId;


                        // Pista
                        document.getElementById(
                            'modal-court-name'
                        ).value = data.court_name ?? '';


                        // Cliente
                        document.getElementById(
                            'modal-client'
                        ).value =
                            data.client_email ??
                            'Reserva interna (sin cliente)';


                        // Fecha
                        document.getElementById(
                            'modal-date'
                        ).value = data.date ?? '';


                        // Hora inicio
                        document.getElementById(
                            'modal-start-time'
                        ).value = data.start_time ?? '';


                        // Hora fin
                        document.getElementById(
                            'modal-end-time'
                        ).value = data.end_time ?? '';


                        // Información
                        document.getElementById(
                            'modal-information'
                        ).value = data.information ?? '';


                        // Estado
                        document.getElementById(
                            'modal-status'
                        ).value =
                            getPaymentStatusText(
                                data.payment_status
                            );


                        // Guardamos si es reserva interna
                        document.getElementById(
                            'modal-information'
                        ).dataset.internal =
                            data.is_internal ? '1' : '0';

                        configurarHorariosModal();

                        // Guardamos los valores originales
                        originalReservationData = {

                            date: data.date ?? '',

                            start_time:
                                data.start_time ?? '',

                            end_time:
                                data.end_time ?? '',

                            information:
                                data.information ?? ''
                        };


                        // Limpiar errores
                        clearModalError();


                        // Abrimos en modo lectura
                        setReservationEditMode(false);


                        infoModal.show();
                    })
                    .catch(err => {

                        alert(
                            err.message ||
                            'No se pudo cargar la reserva.'
                        );
                    });
                },


                // -------------------------------------------------
                // ARRASTRAR UNA RESERVA
                // -------------------------------------------------

                eventDrop: function (info) {

                    const reservationId =
                        info.event.id;

                    const newStart =
                        info.event.start;

                    const newEnd =
                        info.event.end;


                    // No permitir fechas pasadas
                    if (newStart < new Date()) {

                        alert(
                            'No se puede mover una reserva a una fecha pasada.'
                        );

                        info.revert();

                        return;
                    }


                    fetch(
                        `/gestion/reservations/${reservationId}/reschedule`,
                        {
                            method: 'PATCH',

                            headers: {
                                'Content-Type':
                                    'application/json',

                                'Accept':
                                    'application/json',

                                'X-CSRF-TOKEN':
                                   csrfToken,
                            },

                            body: JSON.stringify({

                                start:
                                    toLocalDateTimeString(
                                        newStart
                                    ),

                                end:
                                    toLocalDateTimeString(
                                        newEnd
                                    ),
                            }),
                        }
                    )
                    .then(res => {

                        if (!res.ok) {

                            return res.json()
                                .then(data => {

                                    throw new Error(
                                        data.message
                                    );
                                });
                        }

                        return res.json();
                    })
                    .catch(err => {

                        alert(
                            err.message ||
                            'No se pudo actualizar la reserva.'
                        );

                        info.revert();
                    });
                },


                // -------------------------------------------------
                // SELECCIONAR HUECO VACÍO
                // -------------------------------------------------

                select: function (info) {

                    if (info.start < new Date()) {

                        alert(
                            'No se puede crear una reserva en una fecha pasada.'
                        );

                        calendar.unselect();

                        return;
                    }


                    pendingSelection = {

                        start: info.start,

                        end: info.end
                    };


                    document.getElementById(
                        'quick-create-time-range'
                    ).textContent =
                        `${info.start.toLocaleString('es-ES')} - ` +
                        `${info.end.toLocaleTimeString('es-ES')}`;


                    document.getElementById(
                        'quick-create-information'
                    ).value = '';


                    createModal.show();

                    calendar.unselect();
                }
            }
        );


        calendar.render();


        // ---------------------------------------------------------
        // CREAR RESERVA RÁPIDA
        // ---------------------------------------------------------

        document.getElementById(
            'quick-create-confirm'
        ).addEventListener('click', function () {

            if (!pendingSelection) {
                return;
            }


            const information =
                document.getElementById(
                    'quick-create-information'
                ).value;


            fetch(
                `/gestion/courts/${courtId}/reservations/quick-create`,
                {

                    method: 'POST',

                    headers: {

                        'Content-Type':
                            'application/json',

                        'Accept':
                            'application/json',

                        'X-CSRF-TOKEN':
                            csrfToken,
                    },

                    body: JSON.stringify({

                        start:
                            toLocalDateTimeString(
                                pendingSelection.start
                            ),

                        end:
                            toLocalDateTimeString(
                                pendingSelection.end
                            ),

                        information:
                            information,
                    }),
                }
            )
            .then(res => {

                if (!res.ok) {

                    return res.json()
                        .then(data => {

                            throw new Error(
                                data.message
                            );
                        });
                }

                return res.json();
            })
            .then(() => {

                createModal.hide();

                pendingSelection = null;

                calendar.refetchEvents();
            })
            .catch(err => {

                alert(
                    err.message ||
                    'No se pudo crear la reserva.'
                );
            });
        });


        // ---------------------------------------------------------
        // BOTÓN EDITAR
        // ---------------------------------------------------------

        document.getElementById(
            'modal-edit-btn'
        ).addEventListener('click', function () {

            setReservationEditMode(true);
        });


        // ---------------------------------------------------------
        // BOTÓN CANCELAR EDICIÓN
        // ---------------------------------------------------------

        document.getElementById(
            'modal-cancel-edit-btn'
        ).addEventListener('click', function () {

            if (originalReservationData) {

                document.getElementById(
                    'modal-date'
                ).value =
                    originalReservationData.date;


                document.getElementById(
                    'modal-start-time'
                ).value =
                    originalReservationData.start_time;


                document.getElementById(
                    'modal-end-time'
                ).value =
                    originalReservationData.end_time;


                document.getElementById(
                    'modal-information'
                ).value =
                    originalReservationData.information;
            }


            clearModalError();

            setReservationEditMode(false);
        });


        // ---------------------------------------------------------
        // GUARDAR CAMBIOS
        // ---------------------------------------------------------

        document.getElementById(
            'modal-save-btn'
        ).addEventListener('click', function () {
            const button = this;


            const reservationId =
                document.getElementById(
                    'modal-reservation-id'
                ).value;


            const date =
                document.getElementById(
                    'modal-date'
                ).value;


            const startTime =
                document.getElementById(
                    'modal-start-time'
                ).value;


            const endTime =
                document.getElementById(
                    'modal-end-time'
                ).value;


            const information =
                document.getElementById(
                    'modal-information'
                ).value;


            const isInternal =
                document.getElementById(
                    'modal-information'
                ).dataset.internal === '1';


            // Limpiar errores
            clearModalError();

            // ---------------------------------------------
            // Validación básica
            // ---------------------------------------------

            if (!date || !startTime || !endTime) {
                showModalError(
                    'La fecha y el horario son obligatorios.'
                );

                return;
            }


            if (startTime >= endTime) {
                showModalError(
                    'La hora de finalización debe ser posterior a la hora de inicio.'
                );

                return;
            }

            const timeError = validateReservationTimes();

            if (timeError) {

                showModalError(timeError);

                return;
            }

            // ---------------------------------------------
            // Datos que enviaremos a Laravel
            // ---------------------------------------------

            const body = {

                date: date,

                start_time_only: startTime,

                end_time_only: endTime,
            };


            // Solo las reservas internas
            // pueden modificar information
            if (isInternal) {

                body.information =
                    information;
            }


            // ---------------------------------------------
            // Estado del botón
            // ---------------------------------------------

            button.disabled = true;

            button.innerHTML =
                '<i class="ti ti-loader-2"></i> Guardando...';


            // ---------------------------------------------
            // Petición
            // ---------------------------------------------

            fetch(
                `/gestion/reservations/${reservationId}`,
                {

                    method: 'PUT',

                    headers: {

                        'Content-Type':
                            'application/json',

                        'Accept':
                            'application/json',

                        'X-CSRF-TOKEN':
                            csrfToken,
                    },

                    body: JSON.stringify(body),
                }
            )
            .then(res => {

                if (!res.ok) {

                    return res.json()
                        .then(data => {

                            // Errores de validación
                            if (data.errors) {

                                const messages =
                                    Object.values(
                                        data.errors
                                    )
                                    .flat()
                                    .join('\n');

                                throw new Error(
                                    messages
                                );
                            }


                            throw new Error(
                                data.message ||
                                'No se pudieron guardar los cambios.'
                            );
                        });
                }


                return res.json();
            })
            .then(() => {

                // Actualizar calendario
                calendar.refetchEvents();


                // Actualizar los datos originales
                originalReservationData = {

                    date: date,

                    start_time: startTime,

                    end_time: endTime,

                    information: information
                };


                // Volver a modo lectura
                setReservationEditMode(false);


                button.disabled = false;

                button.innerHTML =
                    '<i class="ti ti-device-floppy"></i> Guardar cambios';


                // Cerrar modal
                infoModal.hide();
            })
            .catch(err => {

                showModalError(
                    err.message ||
                    'No se pudieron guardar los cambios.'
                );


                button.disabled = false;

                button.innerHTML =
                    '<i class="ti ti-device-floppy"></i> Guardar cambios';
            });
        });


        // ---------------------------------------------------------
        // FUNCIONES AUXILIARES
        // ---------------------------------------------------------

        function setReservationEditMode(editing) {
            const date =
                document.getElementById('modal-date');

            const startTime =
                document.getElementById('modal-start-time');

            const endTime =
                document.getElementById('modal-end-time');

            const information =
                document.getElementById('modal-information');

            const isInternal =
                information.dataset.internal === '1';


            date.disabled = !editing;

            // Hora inicio:
            // editable tanto para reservas internas como de clientes
            startTime.disabled = !editing;

            // Hora fin:
            // - interna → editable
            // - cliente → SIEMPRE bloqueada
            endTime.disabled =
                !editing || !isInternal;

            // Información
            information.disabled =
                !editing || !isInternal;


            document.getElementById('modal-edit-btn')
                .classList.toggle(
                    'd-none',
                    editing
                );

            document.getElementById('modal-save-btn')
                .classList.toggle(
                    'd-none',
                    !editing
                );

            document.getElementById('modal-cancel-edit-btn')
                .classList.toggle(
                    'd-none',
                    !editing
                );
        }
        
        function validateReservationTimes() {
            const startTime =
                document.getElementById('modal-start-time').value;

            const endTime =
                document.getElementById('modal-end-time').value;

            const information =
                document.getElementById('modal-information');

            const isInternal =
                information.dataset.internal === '1';

            const openingTime =
                '{{ $openingTime }}';

            const closingTime =
                '{{ $closingTime }}';

            if (!startTime || !endTime) {
                return 'La hora de inicio y la hora de fin son obligatorias.';
            }

            // Comprobar límites de apertura/cierre
            if (
                startTime < openingTime ||
                endTime > closingTime
            ) {
                return `La reserva debe estar entre las ${openingTime} y las ${closingTime}.`;
            }

            // Comprobaciones específicas para clientes
            if (!isInternal) {

                // Debe empezar en hora redonda
                if (!startTime.endsWith(':00')) {
                    return 'Las reservas de clientes deben comenzar en una hora redonda.';
                }

                // Debe terminar en hora redonda
                if (!endTime.endsWith(':00')) {
                    return 'Las reservas de clientes deben terminar en una hora redonda.';
                }

                // Convertimos las horas a minutos
                const [startHour, startMinute] =
                    startTime.split(':').map(Number);

                const [endHour, endMinute] =
                    endTime.split(':').map(Number);

                const startMinutes =
                    startHour * 60 + startMinute;

                const endMinutes =
                    endHour * 60 + endMinute;

                // Exactamente una hora
                if (endMinutes - startMinutes !== 60) {
                    return 'Las reservas de clientes deben tener una duración exacta de una hora.';
                }
            }

            return null;
        }

        function configurarHorariosModal() {
            const startSelect =
                document.getElementById('modal-start-time');

            const endSelect =
                document.getElementById('modal-end-time');

            const information =
                document.getElementById('modal-information');

            const isInternal =
                information.dataset.internal === '1';

            const openingTime =
                '{{ $openingTime }}';

            const closingTime =
                '{{ $closingTime }}';


            // Guardamos los valores actuales
            const currentStart = startSelect.value;
            const currentEnd = endSelect.value;


            // Limpiar opciones
            startSelect.innerHTML = '';
            endSelect.innerHTML = '';


            // ---------------------------------------------------------
            // UTILIDADES
            // ---------------------------------------------------------

            function toMinutes(time) {

                const [hours, minutes] =
                    time.split(':').map(Number);

                return hours * 60 + minutes;
            }


            function toTime(minutes) {

                const hours =
                    Math.floor(minutes / 60);

                const mins =
                    minutes % 60;

                return String(hours).padStart(2, '0')
                    + ':' +
                    String(mins).padStart(2, '0');
            }


            const openingMinutes =
                toMinutes(openingTime);

            const closingMinutes =
                toMinutes(closingTime);


            // =========================================================
            // RESERVA DE CLIENTE
            // =========================================================

            if (!isInternal) {
                const lastStart =
                    closingMinutes - 60;

                // Hora de fin no modificable
                endSelect.disabled = false;

                // Solo horas redondas para el inicio
                for (
                    let minutes = openingMinutes;
                    minutes <= lastStart;
                    minutes += 60
                ) {
                    const time = toTime(minutes);

                    startSelect.appendChild(
                        new Option(time, time)
                    );
                }

                // Cambiar hora de inicio
                startSelect.onchange = 
                    function () {

                        const startMinutes =
                            toMinutes(this.value);

                        const endValue =
                            toTime(startMinutes + 60);

                        endSelect.innerHTML = '';

                        endSelect.appendChild(
                            new Option(
                                endValue,
                                endValue
                            )
                        );

                        endSelect.value = endValue;
                    };

                // Restaurar inicio original
                if (currentStart) {
                    startSelect.value = currentStart;
                }

                // Calcular fin
                if (startSelect.value) {

                    const startMinutes =
                        toMinutes(startSelect.value);

                    const endValue =
                        toTime(startMinutes + 60);

                    endSelect.innerHTML = '';

                    endSelect.appendChild(
                        new Option(
                            endValue,
                            endValue
                        )
                    );

                    endSelect.value = endValue;
                }

                return;
            }

            // =========================================================
            // RESERVA INTERNA
            // =========================================================

            /*
            * Las reservas internas pueden utilizar cualquier minuto
            * entre apertura y cierre.
            */

            for (
                let minutes = openingMinutes;
                minutes <= closingMinutes;
                minutes++
            ) {

                const time =
                    toTime(minutes);

                startSelect.appendChild(
                    new Option(time, time)
                );

                endSelect.appendChild(
                    new Option(time, time)
                );
            }


            // ---------------------------------------------------------
            // RESTAURAR VALORES ORIGINALES
            // ---------------------------------------------------------

            if (currentStart) {

                startSelect.value =
                    currentStart;
            }

            if (currentEnd) {

                endSelect.value =
                    currentEnd;
            }
        }
        
        function clearModalError() {

            const errorBox =
                document.getElementById(
                    'modal-error'
                );


            errorBox.classList.add(
                'd-none'
            );


            errorBox.textContent = '';
        }


        function showModalError(message) {

            const errorBox =
                document.getElementById(
                    'modal-error'
                );


            errorBox.textContent =
                message;


            errorBox.classList.remove(
                'd-none'
            );
        }


        function getPaymentStatusText(status) {

            switch (status) {

                case 'paid':
                    return 'Pagada / Confirmada';

                case 'pending':
                    return 'Pendiente de pago';

                case 'canceled':
                    return 'Cancelada';

                case 'refunded':
                    return 'Reembolsada';

                default:
                    return status ?? '-';
            }
        }


        function toLocalDateTimeString(date) {

            const pad =
                (n) => String(n).padStart(2, '0');


            const year =
                date.getFullYear();


            const month =
                pad(date.getMonth() + 1);


            const day =
                pad(date.getDate());


            const hours =
                pad(date.getHours());


            const minutes =
                pad(date.getMinutes());


            const seconds =
                pad(date.getSeconds());


            return `${year}-${month}-${day} ` +
                   `${hours}:${minutes}:${seconds}`;
        }

    });
</script>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/locales/es.global.min.js"></script>
@endpush