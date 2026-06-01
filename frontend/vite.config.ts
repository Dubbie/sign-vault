import { fileURLToPath, URL } from 'node:url'
import { readFileSync } from 'node:fs'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import tailwindcss from '@tailwindcss/vite'

// https://vite.dev/config/
export default defineConfig(({ command, isPreview }) => {
  const httpsOptions =
    command === 'serve' || isPreview
      ? {
          key: readFileSync(fileURLToPath(new URL('./certs/localhost.key', import.meta.url))),
          cert: readFileSync(fileURLToPath(new URL('./certs/localhost.crt', import.meta.url))),
        }
      : undefined

  return {
    plugins: [vue(), vueDevTools(), tailwindcss()],
    server: httpsOptions ? { https: httpsOptions } : undefined,
    preview: httpsOptions ? { https: httpsOptions } : undefined,
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
      },
    },
  }
})
