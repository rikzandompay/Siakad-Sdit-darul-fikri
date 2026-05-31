import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    build: {
        // Enable CSS minification
        cssMinify: true,
        // Enable JS minification (Vite 8 default: oxc)
        minify: true,
        // Optimize chunk splitting
        rollupOptions: {
            output: {
                // Use content-hash for cache busting
                assetFileNames: 'assets/[name]-[hash][extname]',
                chunkFileNames: 'assets/[name]-[hash].js',
                entryFileNames: 'assets/[name]-[hash].js',
            },
        },
        // Target modern browsers for smaller bundles
        target: 'es2020',
    },
});
