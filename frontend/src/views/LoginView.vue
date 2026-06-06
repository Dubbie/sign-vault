<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const loadingProvider = ref<'discord' | 'trackmania' | null>(null)

if (auth.isAuthenticated) {
  router.replace({ name: 'dashboard' })
}

async function loginWith(provider: 'discord' | 'trackmania') {
  loadingProvider.value = provider
  try {
    await auth.loginWith(provider)
  } finally {
    loadingProvider.value = null
  }
}
</script>

<template>
  <div class="mx-auto max-w-sm">
    <p class="mb-3 text-xs text-emerald-400 font-semibold">Sign in</p>
    <h1 class="text-[clamp(1.5rem,5vw,2rem)] leading-tight text-zinc-100">Welcome to SignVault</h1>
    <p class="mt-2 text-zinc-400 text-sm">Choose a provider to continue.</p>

    <div class="mt-8 flex flex-col gap-3">
      <!-- Discord -->
      <button
        type="button"
        :disabled="loadingProvider !== null"
        class="flex items-center gap-3 rounded-xl border border-outline-variant/30 bg-surface-container-low px-5 py-4 text-left transition hover:border-indigo-500/60 hover:bg-indigo-500/5 disabled:opacity-50 disabled:cursor-not-allowed"
        @click="loginWith('discord')"
      >
        <svg
          class="size-6 shrink-0 text-indigo-400 fill-current"
          viewBox="0 0 127.14 96.36"
          xmlns="http://www.w3.org/2000/svg"
          aria-hidden="true"
        >
          <path
            d="M107.7 8.07A105.15 105.15 0 0 0 81.47 0a72.06 72.06 0 0 0-3.36 6.83 97.68 97.68 0 0 0-29.11 0A72.37 72.37 0 0 0 45.64 0a105.89 105.89 0 0 0-26.25 8.09C2.79 32.65-1.71 56.6.54 80.21a105.73 105.73 0 0 0 32.17 16.15 77.7 77.7 0 0 0 6.89-11.11 68.42 68.42 0 0 1-10.85-5.18c.91-.66 1.8-1.34 2.66-2a75.57 75.57 0 0 0 64.32 0c.87.71 1.76 1.39 2.66 2a68.68 68.68 0 0 1-10.87 5.19 77 77 0 0 0 6.89 11.1 105.25 105.25 0 0 0 32.19-16.14c2.64-27.38-4.51-51.11-18.9-72.15ZM42.45 65.69C36.18 65.69 31 60 31 53s5-12.74 11.43-12.74S54 46 53.89 53s-5.05 12.69-11.44 12.69Zm42.24 0C78.41 65.69 73.25 60 73.25 53s5-12.74 11.44-12.74S96.23 46 96.12 53s-5.04 12.69-11.43 12.69Z"
          />
        </svg>
        <div class="flex-1">
          <p class="font-medium text-zinc-100 text-sm">
            {{ loadingProvider === 'discord' ? 'Redirecting...' : 'Continue with Discord' }}
          </p>
          <p class="text-xs text-zinc-500 mt-0.5">Sign in using your Discord account</p>
        </div>
      </button>

      <!-- Trackmania / Ubisoft -->
      <button
        type="button"
        :disabled="loadingProvider !== null"
        class="flex items-center gap-3 rounded-xl border border-outline-variant/30 bg-surface-container-low px-5 py-4 text-left transition hover:border-blue-500/60 hover:bg-blue-500/5 disabled:opacity-50 disabled:cursor-not-allowed"
        @click="loginWith('trackmania')"
      >
        <svg
          class="size-6 shrink-0 text-blue-400"
          viewBox="0 0 24 24"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
          aria-hidden="true"
        >
          <path
            d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
        <div class="flex-1">
          <p class="font-medium text-zinc-100 text-sm">
            {{ loadingProvider === 'trackmania' ? 'Redirecting...' : 'Continue with Trackmania' }}
          </p>
          <p class="text-xs text-zinc-500 mt-0.5">Sign in using your Ubisoft / Trackmania account</p>
        </div>
      </button>
    </div>
  </div>
</template>
