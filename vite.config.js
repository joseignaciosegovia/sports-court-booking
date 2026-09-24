import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/css/public.css',
                'resources/css/calendar.css',
                'resources/css/subtitle.css',
                'resources/css/form.css',
                'resources/css/responsive.css',

                'resources/js/app.js',
                'resources/js/layout.js',
                'resources/js/countdown-timer.js',
                'resources/js/court-reservation-calendar.js',
                'resources/js/calendar-manager.js',
                'resources/js/public-courts-calendar.js',
                'resources/js/validation.js'
            ],
            refresh: true,
        }),
    ],
});
