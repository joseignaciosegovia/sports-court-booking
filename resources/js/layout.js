// Alterna la visibilidad de la barra de navegación en dispositivos móviles
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('btnMenu')?.addEventListener('click', () => {
        document.querySelector('.sidebar').classList.toggle('abierta');
        document.getElementById('overlay').classList.toggle('visible');
    });

    const modalElement = document.getElementById('successModal');
    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }
});