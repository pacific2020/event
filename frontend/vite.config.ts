import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    vueDevTools(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
  build: {
    // This increases the limit slightly to 1000kb but we'll focus on splitting
    chunkSizeWarningLimit: 1000,
    rollupOptions: {
      output: {
        // This is where the magic happens
        manualChunks(id) {
          // Creates a separate chunk for vendor libraries in node_modules
          if (id.includes('node_modules')) {
            // Group common heavy hitters together
            if (id.includes('vue') || id.includes('pinia') || id.includes('vue-router')) {
              return 'vendor-core';
            }
            // Group other utilities (axios, toastification, etc)
            return 'vendor-utils';
          }
        }
      }
    }
  }
})