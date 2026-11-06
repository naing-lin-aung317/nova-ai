import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite-plus';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/nova.css', 'resources/js/app.ts'],
            refresh: true,
        }),
        inertia(),
        vue(),
        wayfinder({
            formVariants: true,
            skipDatabase: true,
        }),
    ],
});
