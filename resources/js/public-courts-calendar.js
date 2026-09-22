document.addEventListener('DOMContentLoaded', function () {

    // ---------------------------------------------------------
    // CONFIGURACIÓN (inyectada desde Blade vía data-* attributes)
    // ---------------------------------------------------------

    const calendarSection = document.getElementById('calendar-section');
    const calendarEl = document.getElementById('calendar');
    const selectedCourtName = document.getElementById('selected-court-name');

    const config = {
        openingTime: calendarSection.dataset.openingTime,
        closingTime: calendarSection.dataset.closingTime,
        scheduleUrlTemplate: calendarSection.dataset.scheduleUrlTemplate,
    };

    function buildScheduleUrl(courtId) {
        return config.scheduleUrlTemplate.replace('COURT_ID', courtId);
    }

    let calendar = null;

    function initCalendar(courtId) {

        // Si ya había un calendario (porque habíamos pinchado previamente
        // en una pista) lo destruimos para crear el nuevo
        if (calendar) {
            calendar.destroy();
        }

        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            locale: 'es',
            slotMinTime: `${config.openingTime}:00`,
            slotMaxTime: `${config.closingTime}:00`,
            slotDuration: '01:00:00',
            hiddenDays: [6, 0],
            height: 'auto',
            allDaySlot: false,
            selectable: true,
            selectOverlap: false,

            // Ruta que devuelve los horarios ocupados de esta pista
            events: buildScheduleUrl(courtId),

            // Formato de la columna que indica la hora
            slotLabelFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false,
            },

            headerToolbar: {
                left: 'prev,next,today',
                center: 'title',
                right: 'timeGridWeek,timeGridDay',
            },
        });

        calendar.render();
    }

    // ---------------------------------------------------------
    // Si pinchamos en una pista (dentro de los acordeones)
    // ---------------------------------------------------------

    document.querySelectorAll('.court-link').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            const courtId = this.dataset.courtId;
            const courtName = this.dataset.courtName;

            // Resalta visualmente la pista elegida
            document.querySelectorAll('.court-link').forEach(l =>
                l.classList.remove('fw-bold', 'text-primary')
            );

            this.classList.add('fw-bold', 'text-primary');

            selectedCourtName.textContent = courtName;
            calendarSection.classList.remove('d-none');

            initCalendar(courtId);

            // Scroll suave hasta el calendario, útil sobre todo en móvil
            calendarSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

});