import { exec } from 'node:child_process';
import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

/**
 * Automatically export OpenAPI specification and regenerate TypeScript types
 * whenever an API Request, Resource, Controller, or Route file changes.
 */
function scrambleWatcher() {
    return {
        name: 'scramble-auto-update',
        handleHotUpdate({ file }) {
            if (
                file.includes('/app/Http/Requests/') ||
                file.includes('/app/Http/Resources/') ||
                file.includes('/app/Http/Controllers/') ||
                file.includes('/routes/api.php')
            ) {
                exec('php artisan scramble:export && npx openapi-typescript api.json -o types/schema.d.ts', (err) => {
                    if (!err) {
                        console.log('⚡ [scramble] OpenAPI specification & TypeScript types updated.');
                    }
                });
            }
        },
    };
}

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
        scrambleWatcher(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
