import { fileURLToPath, URL } from 'node:url'

import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const hmrClientPort = Number(env.VITE_HMR_CLIENT_PORT ?? 8080)

  return {
    plugins: [vue(), vueDevTools()],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
      },
    },
    server: {
      host: '0.0.0.0',
      port: 5173,
      strictPort: true,
      // The browser hits Vite through nginx on host port WEB_PORT (default 8080),
      // so HMR's WebSocket must connect back to that port, not to 5173.
      hmr: {
        clientPort: hmrClientPort,
      },
      watch: {
        // Polling is required for reliable file change detection inside Docker on macOS.
        usePolling: true,
        interval: 300,
      },
    },
  }
})
