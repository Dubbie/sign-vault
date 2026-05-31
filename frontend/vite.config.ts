import { fileURLToPath, URL } from 'node:url'
import { readFileSync } from 'node:fs'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import tailwindcss from '@tailwindcss/vite'

// https://vite.dev/config/
const httpsOptions = {
  key: readFileSync(fileURLToPath(new URL('./certs/localhost.key', import.meta.url))),
  cert: readFileSync(fileURLToPath(new URL('./certs/localhost.crt', import.meta.url))),
}

export default defineConfig({
  plugins: [vue(), vueDevTools(), tailwindcss()],
  server: {
    https: httpsOptions,
  },
  preview: {
    https: httpsOptions,
  },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
})
