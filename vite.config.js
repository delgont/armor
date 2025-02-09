import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    build: {
        outDir: '../../public/build-armor',
        emptyOutDir: true,
        manifest: true,
    },
    plugins: [
        laravel({
            publicDirectory: '../../public',
            buildDirectory: 'build-armor',
            input: [
                __dirname + '/resources/sass/app.scss',
                __dirname + '/resources/js/app.js'
            ],
            refresh: true,
        }),
    ],
});
