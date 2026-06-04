import { fileURLToPath, URL } from 'node:url'
import { readFileSync } from 'node:fs'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig(({ command, isPreview }) => {
  const httpsOptions =
    command === 'serve' || isPreview
      ? {
          key: readFileSync(fileURLToPath(new URL('./certs/localhost.key', import.meta.url))),
          cert: readFileSync(fileURLToPath(new URL('./certs/localhost.crt', import.meta.url))),
        }
      : undefined

  return {
    plugins: [vue(), tailwindcss()],
    server: { port: 5174, ...(httpsOptions ? { https: httpsOptions } : {}) },
    preview: { port: 5174, ...(httpsOptions ? { https: httpsOptions } : {}) },
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
      },
    },
  }
})
