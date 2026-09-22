// Alterna la visibilidad de la barra de navegación en dispositivos móviles
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('overlay');

    document.getElementById('btnMenu')?.addEventListener('click', () => {
        sidebar?.classList.toggle('abierta');
        overlay?.classList.toggle('visible');
    });

    // Cierra la barra de navegación al pinchar fuera de ella
    overlay?.addEventListener('click', () => {
        sidebar?.classList.remove('abierta');
        overlay.classList.remove('visible');
    });

    const modalElement = document.getElementById('successModal');
    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }
});