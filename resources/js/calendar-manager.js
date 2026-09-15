document.addEventListener('DOMContentLoaded', function () {
    // Configuración (utilizando los datos obtenidos de la vista) 

    const calendarEl = document.getElementById('calendar');

    const config = {
        courtId: calendarEl.dataset.courtId,
        openingTime: calendarEl.dataset.openingTime,
        closingTime: calendarEl.dataset.closingTime,
        eventsUrl: calendarEl.dataset.eventsUrl,
        reservationInfoUrlTemplate: calendarEl.dataset.reservationInfoUrlTemplate,
        reservationRescheduleUrlTemplate: calendarEl.dataset.reservationRescheduleUrlTemplate,
        reservationUpdateUrlTemplate: calendarEl.dataset.reservationUpdateUrlTemplate,
        quickCreateUrl: calendarEl.dataset.quickCreateUrl,
    };

    function buildUrl(template, reservationId) {
        return template.replace('RESERVATION_ID', reservationId);
    }

    const csrfToken =
        document.querySelector('meta[name="csrf-token"]').content;

    // Modales

    const infoModalEl =
        document.getElementById('reservationInfoModal');

    const infoModal =
        new bootstrap.Modal(infoModalEl);

    const createModalEl =
        document.getElementById('quickCreateModal');

    const createModal =
        new bootstrap.Modal(createModalEl);

    // Variables

    let pendingSelection = null;

    let originalReservationData = null;

    // Calendario

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        locale: 'es',
        slotMinTime: `${config.openingTime}:00`,
        slotMaxTime: `${config.closingTime}:00`,
        slotDuration: '01:00:00',
        height: 'auto',
        allDaySlot: false,
        selectable: true,
        selectOverlap: false,
        eventOverlap: false,
        editable: true,
        eventStartEditable: true,
        eventDurationEditable: false,

        events: config.eventsUrl,

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

        // Pulsamos click en una reserva

        eventClick: function (info) {

            const reservationId = info.event.id;

            fetch(buildUrl(config.reservationInfoUrlTemplate, reservationId))
                .then(res => {

                    if (!res.ok) {
                        throw new Error(
                            'No se pudo obtener la información de la reserva.'
                        );
                    }

                    return res.json();
                })
                // Datos de la reserva
                .then(data => {

                    document.getElementById('modal-reservation-id').value =
                        reservationId;

                    document.getElementById('modal-court-name').value =
                        data.court_name ?? '';

                    document.getElementById('modal-client').value =
                        data.client_email ?? 'Reserva interna (sin cliente)';

                    document.getElementById('modal-date').value =
                        data.date ?? '';

                    document.getElementById('modal-start-time').value =
                        data.start_time ?? '';

                    document.getElementById('modal-end-time').value =
                        data.end_time ?? '';

                    document.getElementById('modal-information').value =
                        data.information ?? '';

                    document.getElementById('modal-status').value =
                        getPaymentStatusText(data.payment_status);

                    // Guardamos si la reserva es interna (si la ha hecho la gestión)
                    document.getElementById('modal-information').dataset.internal =
                        data.is_internal ? '1' : '0';

                    configurarHorariosModal();

                    // Guardamos los valores originales
                    originalReservationData = {
                        date: data.date ?? '',
                        start_time: data.start_time ?? '',
                        end_time: data.end_time ?? '',
                        information: data.information ?? '',
                    };

                    // Limpiamos errores
                    clearModalError();

                    // Abrimos en modo lectura
                    setReservationEditMode(false);

                    infoModal.show();
                })
                .catch(err => {
                    alert(err.message || 'No se pudo cargar la reserva.');
                });
        },

        // Arrastramos una reserva a una nueva fecha

        eventDrop: function (info) {

            const reservationId = info.event.id;
            const newStart = info.event.start;
            const newEnd = info.event.end;

            // No permitimos mover la reserva a fechas pasadas
            if (newStart < new Date()) {
                alert('No se puede mover una reserva a una fecha pasada.');
                info.revert();
                return;
            }

            fetch(buildUrl(config.reservationRescheduleUrlTemplate, reservationId), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    start: toLocalDateTimeString(newStart),
                    end: toLocalDateTimeString(newEnd),
                }),
            })
                .then(res => {

                    if (!res.ok) {
                        return res.json().then(data => {
                            throw new Error(data.message);
                        });
                    }

                    return res.json();
                })
                .catch(err => {
                    alert(err.message || 'No se pudo actualizar la reserva.');
                    info.revert();
                });
        },


        // Pinchamos en un hueco vacío del calendario

        select: function (info) {

            // Si pinchamos en una fecha pasada, no se permite crear una reserva
            if (info.start < new Date()) {
                alert('No se puede crear una reserva en una fecha pasada.');
                calendar.unselect();
                return;
            }

            pendingSelection = {
                start: info.start,
                end: info.end,
            };

            document.getElementById('quick-create-time-range').textContent =
                `${info.start.toLocaleString('es-ES')} - ` +
                `${info.end.toLocaleTimeString('es-ES')}`;

            document.getElementById('quick-create-information').value = '';

            createModal.show();

            calendar.unselect();
        },
    });

    calendar.render();

    // Creamos una reserva tras pinchar en un hueco vacío

    document.getElementById('quick-create-confirm')
        .addEventListener('click', function () {

            if (!pendingSelection) {
                return;
            }

            const information =
                document.getElementById('quick-create-information').value;

            fetch(config.quickCreateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    start: toLocalDateTimeString(pendingSelection.start),
                    end: toLocalDateTimeString(pendingSelection.end),
                    information: information,
                }),
            })
                .then(res => {

                    if (!res.ok) {
                        return res.json().then(data => {
                            throw new Error(data.message);
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
                    alert(err.message || 'No se pudo crear la reserva.');
                });
        });


    // Pulsamos en editar la reserva

    document.getElementById('modal-edit-btn')
        .addEventListener('click', function () {
            setReservationEditMode(true);
        });


    // Pulsamos en cancelar la edición de la reserva

    document.getElementById('modal-cancel-edit-btn')
        .addEventListener('click', function () {

            if (originalReservationData) {
                document.getElementById('modal-date').value =
                    originalReservationData.date;

                document.getElementById('modal-start-time').value =
                    originalReservationData.start_time;

                document.getElementById('modal-end-time').value =
                    originalReservationData.end_time;

                document.getElementById('modal-information').value =
                    originalReservationData.information;
            }

            clearModalError();
            setReservationEditMode(false);
        });


    // Pulsamos en guardar cambios

    document.getElementById('modal-save-btn')
        .addEventListener('click', function () {

            const button = this;

            const reservationId =
                document.getElementById('modal-reservation-id').value;

            const date =
                document.getElementById('modal-date').value;

            const startTime =
                document.getElementById('modal-start-time').value;

            const endTime =
                document.getElementById('modal-end-time').value;

            const information =
                document.getElementById('modal-information').value;

            const isInternal =
                document.getElementById('modal-information').dataset.internal === '1';

            // Limpiamos errores
            clearModalError();

            // Validación básica

            if (!date || !startTime || !endTime) {
                showModalError('La fecha y el horario son obligatorios.');
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

            // Datos que enviaremos a Laravel

            const body = {
                date: date,
                start_time_only: startTime,
                end_time_only: endTime,
            };

            // Solo las reservas internas pueden modificar information
            if (isInternal) {
                body.information = information;
            }

            // Estado del botón

            button.disabled = true;
            button.innerHTML = '<i class="ti ti-loader-2"></i> Guardando...';

            // Petición

            fetch(buildUrl(config.reservationUpdateUrlTemplate, reservationId), {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(body),
            })
                .then(res => {

                    if (!res.ok) {
                        return res.json().then(data => {

                            // Errores de validación
                            if (data.errors) {
                                const messages =
                                    Object.values(data.errors).flat().join('\n');

                                throw new Error(messages);
                            }

                            throw new Error(
                                data.message || 'No se pudieron guardar los cambios.'
                            );
                        });
                    }

                    return res.json();
                })
                .then(() => {

                    // Actualizamos el calendario
                    calendar.refetchEvents();

                    // Actualizamos los datos originales
                    originalReservationData = {
                        date: date,
                        start_time: startTime,
                        end_time: endTime,
                        information: information,
                    };

                    // Volvemos a modo lectura
                    setReservationEditMode(false);

                    button.disabled = false;
                    button.innerHTML =
                        '<i class="ti ti-device-floppy"></i> Guardar cambios';

                    // Cerramos el modal
                    infoModal.hide();
                })
                .catch(err => {
                    showModalError(
                        err.message || 'No se pudieron guardar los cambios.'
                    );

                    button.disabled = false;
                    button.innerHTML =
                        '<i class="ti ti-device-floppy"></i> Guardar cambios';
                });
        });

    // Funciones auxiliares


    function setReservationEditMode(editing) {

        const date = document.getElementById('modal-date');
        const startTime = document.getElementById('modal-start-time');
        const endTime = document.getElementById('modal-end-time');
        const information = document.getElementById('modal-information');
        const isInternal = information.dataset.internal === '1';

        date.disabled = !editing;

        // Hora inicio: editable tanto para reservas internas como de clientes
        startTime.disabled = !editing;

        // Hora fin:
        // Reserva interna: editable
        // Reserva de cliente: bloqueada (siempre será una hora después de la hora inicio)
        endTime.disabled = !editing || !isInternal;

        // Información
        information.disabled = !editing || !isInternal;

        document.getElementById('modal-edit-btn')
            .classList.toggle('d-none', editing);

        document.getElementById('modal-save-btn')
            .classList.toggle('d-none', !editing);

        document.getElementById('modal-cancel-edit-btn')
            .classList.toggle('d-none', !editing);
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

        const openingTime = config.openingTime;
        const closingTime = config.closingTime;

        if (!startTime || !endTime) {
            return 'La hora de inicio y la hora de fin son obligatorias.';
        }

        // Comprobamos límites de apertura/cierre
        if (startTime < openingTime || endTime > closingTime) {
            return `La reserva debe estar entre las ${openingTime} y las ${closingTime}.`;
        }

        // Comprobaciones específicas para clientes
        if (!isInternal) {

            // La reserva debe empezar en hora redonda
            if (!startTime.endsWith(':00')) {
                return 'Las reservas de clientes deben comenzar en una hora redonda.';
            }

            // La reserva debe terminar en hora redonda
            if (!endTime.endsWith(':00')) {
                return 'Las reservas de clientes deben terminar en una hora redonda.';
            }

            // Convertimos las horas a minutos
            const [startHour, startMinute] = startTime.split(':').map(Number);
            const [endHour, endMinute] = endTime.split(':').map(Number);

            const startMinutes = startHour * 60 + startMinute;
            const endMinutes = endHour * 60 + endMinute;

            // Exactamente una hora
            if (endMinutes - startMinutes !== 60) {
                return 'Las reservas de clientes deben tener una duración exacta de una hora.';
            }
        }

        return null;
    }

    function configurarHorariosModal() {

        const startSelect = document.getElementById('modal-start-time');
        const endSelect = document.getElementById('modal-end-time');
        const information = document.getElementById('modal-information');
        const isInternal = information.dataset.internal === '1';

        const openingTime = config.openingTime;
        const closingTime = config.closingTime;

        // Guardamos los valores actuales
        const currentStart = startSelect.value;
        const currentEnd = endSelect.value;

        // Limpiamos opciones
        startSelect.innerHTML = '';
        endSelect.innerHTML = '';

        // Utilidades

        function toMinutes(time) {
            const [hours, minutes] = time.split(':').map(Number);
            return hours * 60 + minutes;
        }

        function toTime(minutes) {
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;

            return String(hours).padStart(2, '0') + ':' +
                String(mins).padStart(2, '0');
        }

        const openingMinutes = toMinutes(openingTime);
        const closingMinutes = toMinutes(closingTime);

        // Si es una reserva del cliente

        if (!isInternal) {

            const lastStart = closingMinutes - 60;

            // Hora de fin no es modificable directamente
            endSelect.disabled = false;

            // Solo horas redondas para el inicio
            for (let minutes = openingMinutes; minutes <= lastStart; minutes += 60) {
                const time = toTime(minutes);
                startSelect.appendChild(new Option(time, time));
            }

            // Cambiamos hora de inicio
            startSelect.onchange = function () {

                const startMinutes = toMinutes(this.value);
                const endValue = toTime(startMinutes + 60);

                endSelect.innerHTML = '';
                endSelect.appendChild(new Option(endValue, endValue));
                endSelect.value = endValue;
            };

            // Restauramos inicio original
            if (currentStart) {
                startSelect.value = currentStart;
            }

            // Calculamos fin
            if (startSelect.value) {

                const startMinutes = toMinutes(startSelect.value);
                const endValue = toTime(startMinutes + 60);

                endSelect.innerHTML = '';
                endSelect.appendChild(new Option(endValue, endValue));
                endSelect.value = endValue;
            }

            return;
        }

        // Reserva interna (hecha por gestión)

        /*
        * Las reservas internas pueden utilizar cualquier minuto
        * entre apertura y cierre.
        */

        for (let minutes = openingMinutes; minutes <= closingMinutes; minutes++) {

            const time = toTime(minutes);

            startSelect.appendChild(new Option(time, time));
            endSelect.appendChild(new Option(time, time));
        }

        // Restaurar valores originales

        if (currentStart) {
            startSelect.value = currentStart;
        }

        if (currentEnd) {
            endSelect.value = currentEnd;
        }
    }

    function clearModalError() {

        const errorBox = document.getElementById('modal-error');

        errorBox.classList.add('d-none');
        errorBox.textContent = '';
    }

    function showModalError(message) {

        const errorBox = document.getElementById('modal-error');

        errorBox.textContent = message;
        errorBox.classList.remove('d-none');
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

        const pad = (n) => String(n).padStart(2, '0');

        const year = date.getFullYear();
        const month = pad(date.getMonth() + 1);
        const day = pad(date.getDate());
        const hours = pad(date.getHours());
        const minutes = pad(date.getMinutes());
        const seconds = pad(date.getSeconds());

        return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }

});