
document.addEventListener('DOMContentLoaded', function () {
    const timers = document.querySelectorAll('.countdown-timer');

    function updateTimers() {
        const now = new Date().getTime();
        let anyExpired = false;

        timers.forEach(function (el) {
            const expiresAt = new Date(el.dataset.expires).getTime();
            const remaining = expiresAt - now;
            const textEl = el.querySelector('.countdown-text');

            if (remaining <= 0) {
                textEl.textContent = 'Plazo expirado';
                el.classList.add('countdown-expired');
                anyExpired = true;
                return;
            }

            const minutes = Math.floor(remaining / 60000);
            const seconds = Math.floor((remaining % 60000) / 1000);
            textEl.textContent = `Quedan ${minutes}:${seconds.toString().padStart(2, '0')} para pagar`;

            el.classList.toggle('countdown-urgent', remaining <= 60000);
        });

        if (anyExpired) {
            setTimeout(() => window.location.reload(), 3000);
        }
    }

    updateTimers();
    setInterval(updateTimers, 1000);
});