import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

const host = process.env.VITE_DEV_HOST ?? 'localhost';
const port = Number(process.env.VITE_PORT ?? 5173);

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        port,
        strictPort: true,
        origin: `http://${host}:${port}`,
        cors: true,
        hmr: {
            host,
            protocol: 'ws',
            clientPort: port,
        },
        watch: {
            ignored: ['**/storage/framework/views/**', '**/vendor/**'],
            usePolling: true,
            interval: 300,
        },
    },
});

