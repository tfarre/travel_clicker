import { defineConfig } from 'vite';
import symfonyPlugin from 'vite-plugin-symfony';
import { svelte } from '@sveltejs/vite-plugin-svelte';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        svelte(),
        tailwindcss(),
        symfonyPlugin({
            stimulus: true,
        }),
    ],
    build: {
        rollupOptions: {
            input: {
                app: './assets/app.js',
            },
        },
    },
});
