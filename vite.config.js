import { defineConfig } from 'vite'

export default defineConfig(({ command }) => ({
    base: command === 'serve' ? '' : '/dist/',
    build: {
        manifest: true,
        outDir: './web/dist/',
        rolldownOptions: {
            input: { app: './src/js/app.js' }
        }
    },
    server: {
        origin: 'http://localhost:5173',
        cors: { origin: /^https?:\/\/.*\.test(:\d+)?$/ }
    }
}))