document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('form-update-reservations');
    const isClientReservation = form.dataset.isClientReservation === '1';

    if (!isClientReservation) {
        return;
    }

    const startSelect = document.getElementById('start_time_only');
    const endSelect = document.getElementById('end_time_only');
    const endHidden = document.getElementById('end_time_only_hidden');

    function recalcularFin() {

        if (!startSelect.value) {
            return;
        }

        const [hour] = startSelect.value.split(':').map(Number);
        const endValue = String(hour + 1).padStart(2, '0') + ':00';

        endSelect.innerHTML = '';
        endSelect.appendChild(new Option(endValue, endValue));
        endSelect.value = endValue;

        endHidden.value = endValue;
    }

    startSelect.addEventListener('change', recalcularFin);

    recalcularFin();
});