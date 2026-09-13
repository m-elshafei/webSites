// Laravel 13 defaults + the bits Vite needs when it runs inside a container.
// The server binds to 0.0.0.0 in the container, while the HMR client in the
// browser must keep talking to localhost:5173 on the host.
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

const host = process.env.VITE_DEV_HOST ?? 'localhost';
const port = Number(process.env.VITE_PORT ?? 5173);

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        port,
        strictPort: true,            // fail loudly instead of drifting to 5174
        origin: `http://${host}:${port}`,
        cors: true,
        hmr: {
            host,                    // what the browser dials back
            protocol: 'ws',
            clientPort: port,
        },
        watch: {
            ignored: ['**/storage/framework/views/**', '**/vendor/**'],
            // Bind mounts do not forward inotify events reliably on
            // macOS/Windows. Harmless on Linux, so it stays on.
            usePolling: true,
            interval: 300,
        },
    },
});
