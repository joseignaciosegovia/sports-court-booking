document.addEventListener('DOMContentLoaded', function () {
    const calendarSection = document.getElementById('calendar-section');
    const calendarEl = document.getElementById('calendar');
    const openingTime = calendarEl.dataset.openingTime;
    const closingTime = calendarEl.dataset.closingTime;
    const eventsUrlTemplate = calendarEl.dataset.eventsUrlTemplate;

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
            slotMinTime: `${openingTime}:00`,
            slotMaxTime: `${closingTime}:00`,
            slotDuration: '01:00:00',
            hiddenDays: [6, 0],
            height: 'auto',
            allDaySlot: false,
            selectable: true,
            selectOverlap: false,
            events: eventsUrlTemplate.replace('__COURT_ID__', courtId),

            slotLabelFormat: {
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

            select: function (info) {
                const now = new Date();

                if (info.start < now) {
                    calendar.unselect();
                    summaryBox.classList.add('d-none');
                    return;
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

            document.querySelectorAll('.court-link').forEach(l => l.classList.remove('fw-bold', 'text-primary'));
            this.classList.add('fw-bold', 'text-primary');

            selectedCourtName.textContent = courtName;
            summaryBox.classList.add('d-none');
            calendarSection.classList.remove('d-none');

            initCalendar(courtId, courtPrice);

            calendarSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
});