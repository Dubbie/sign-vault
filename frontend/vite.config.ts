import { readFileSync } from 'node:fs'
import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import tailwindcss from '@tailwindcss/vite'

import { readReleaseMetadata } from './release-metadata'

// https://vite.dev/config/
export default defineConfig(({ command, isPreview }) => {
  const repoRoot = fileURLToPath(new URL('..', import.meta.url))
  const releaseMetadata = readReleaseMetadata(repoRoot)
  const httpsOptions =
    command === 'serve' || isPreview
      ? {
          key: readFileSync(fileURLToPath(new URL('./certs/localhost.key', import.meta.url))),
          cert: readFileSync(fileURLToPath(new URL('./certs/localhost.crt', import.meta.url))),
        }
      : undefined

  return {
    plugins: [vue(), vueDevTools(), tailwindcss()],
    server: { port: 5173, ...(httpsOptions ? { https: httpsOptions } : {}) },
    preview: { port: 5173, ...(httpsOptions ? { https: httpsOptions } : {}) },
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
      },
    },
    define: {
      __APP_VERSION__: JSON.stringify(releaseMetadata.version),
      __APP_RELEASE_DATE__: JSON.stringify(releaseMetadata.releaseDate),
      __APP_RELEASE_NOTES__: JSON.stringify(releaseMetadata.releaseNotes),
    },
  }
})
