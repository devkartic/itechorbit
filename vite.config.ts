import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwind from '@tailwindcss/vite';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig(({ mode }) => ({
  plugins: [laravel({ input: ['resources/js/app.tsx'], ssr: 'resources/js/ssr.tsx', refresh: true }), react(), tailwind()],
  resolve: { alias: { '@': fileURLToPath(new URL('./resources/js', import.meta.url)) } },
  build: { sourcemap: false },
  server: {
    host: '127.0.0.1',
    port: 5173,
    strictPort: true,
    cors: { origin: [loadEnv(mode, process.cwd(), '').APP_URL || 'http://localhost', 'http://localhost', 'http://127.0.0.1'] },
    hmr: { host: '127.0.0.1', port: 5173 },
  },
}));
