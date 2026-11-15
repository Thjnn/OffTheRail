import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

// Cả hai font đều có bộ chữ tiếng Việt (Instrument Sans của bản cũ thì không).
const subsets = ['latin', 'vietnamese'];

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/admin.css', 'resources/js/admin.js', 'resources/js/labels.js'],
            refresh: true,
            fonts: [
                bunny('Be Vietnam Pro', { weights: [400, 500, 600, 700], subsets, preload: false }),
                bunny('Fraunces', { weights: [400, 600], styles: ['normal', 'italic'], subsets, preload: false }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
