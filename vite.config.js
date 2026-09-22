// vite imports
import { defineConfig } from 'vite';
import legacy from '@vitejs/plugin-legacy';
import ViteRestart from "vite-plugin-restart";
import { compression } from 'vite-plugin-compression2'

import * as path from 'path';
import { nodeResolve } from '@rollup/plugin-node-resolve';

export default defineConfig(({command}) => ({
    base: command === 'serve' ? '' : '/dist/',
    // Optimize esbuild for faster TypeScript compilation
    esbuild: {
        target: 'es2022',
        // Removes console.log etc. during production builds
        drop: command === 'build' ? ['console', 'debugger'] : [],
    },
    build: {
        emptyOutDir: true,
        manifest: true,
        outDir: path.resolve('./web/dist/'),
        // Target modern browsers for smaller output
        target: 'es2022',
        // Enable chunk size warnings
        chunkSizeWarningLimit: 1000,
        // Use esbuild for faster minification (switch from terser)
        minify: 'esbuild',
        // Enable CSS minification
        cssMinify: 'esbuild',
        // Terser options kept for reference (if minify: 'terser' is used)
        terserOptions: {
            compress: {
                drop_console: command === 'build', // Remove console.log in production
                drop_debugger: true,
                pure_funcs: ['console.log', 'console.info', 'console.debug'], // Remove specific console methods
            },
            format: {
                comments: false, // Remove comments
            },
        },
        rollupOptions: {
            input: {
                app: './src/main.ts',
            },
            output: {
                // Manual chunk splitting for better caching
                manualChunks: (id) => {
                    // Vendor chunks
                    if (id.includes('node_modules')) {
                        // Alpine.js and plugins
                        if (id.includes('alpinejs') || id.includes('@alpinejs')) {
                            return 'alpine';
                        }
                        // Swiper
                        if (id.includes('swiper')) {
                            return 'swiper';
                        }
                        // Lucide icons
                        if (id.includes('lucide')) {
                            return 'lucide';
                        }
                        // Vanilla Tilt
                        if (id.includes('vanilla-tilt')) {
                            return 'vanilla-tilt';
                        }
                        // Tailwind CSS utilities
                        if (id.includes('tailwindcss')) {
                            return 'tailwind';
                        }
                        // Other vendor libraries
                        return 'vendor';
                    }
                },
                // Optimize chunk file names for better caching
                chunkFileNames: 'assets/[name]-[hash].js',
                entryFileNames: 'assets/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash].[ext]',
            },
        },
        // Enable source maps for production debugging (optional, increases build size)
        sourcemap: false,
    },
    server: {
        port: 8080,
        hmr : {
            host: 'localhost'
        },
        strictPort: true,
    },
    plugins: [
        legacy(),
        ViteRestart({
            restart: [
                './templates/**/*',
                './src/**/*',
            ],
        }),
        nodeResolve({
            modulePaths: [
                path.resolve('./node_modules'),
            ],
        }),
        compression()
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'src'),
        },
        preserveSymlinks: true,
    }
}));