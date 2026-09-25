import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // CSS comunes
                'resources/css/app.css',
                'resources/css/body.css',
                'resources/css/header.css',
                'resources/css/footer.css',

                // CSS de usuarios
                'resources/css/navbar.css',
                'resources/css/subtitle.css',
                'resources/css/welcome.css',
                'resources/css/responsive.css',

                // CSS específicos
                'resources/css/public.css',
                'resources/css/calendar.css',
                'resources/css/form.css',
                'resources/css/dashboard.css',
                'resources/css/table.css',

                // JavaScript
                'resources/js/app.js',
                'resources/js/layout.js',
                'resources/js/countdown-timer.js',
                'resources/js/court-reservation-calendar.js',
                'resources/js/calendar-manager.js',
                'resources/js/public-courts-calendar.js',
                'resources/js/validation.js',
            ],
            refresh: true,
        }),
    ],
});