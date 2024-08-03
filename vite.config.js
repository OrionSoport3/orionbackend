import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

const APP_URL = process.env.APP_URL || 'http://mart.test';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        },
    ),
],
});
