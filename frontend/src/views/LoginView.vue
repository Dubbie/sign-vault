<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'
import { ShieldCheck } from '@lucide/vue'

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
    <div class="glass-card rounded-xl p-6 text-center">
      <h1 class="text-headline-lg text-on-surface">Welcome to SignVault</h1>
      <p class="mt-2 text-on-surface-variant text-sm">Choose a provider to continue.</p>

      <div class="mt-8 flex flex-col gap-3">
        <!-- Discord -->
        <button
          type="button"
          :disabled="loadingProvider !== null"
          class="cursor-pointer flex justify-center items-center gap-3 rounded-lg bg-[#5865f2] px-4 py-2 transition hover:ring-offset-surface hover:ring-offset-2 hover:ring-2 hover:ring-primary disabled:opacity-50 disabled:cursor-not-allowed"
          @click="loginWith('discord')"
        >
          <svg
            class="size-6 shrink-0 text-white fill-current"
            viewBox="0 0 127.14 96.36"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
          >
            <path
              d="M107.7 8.07A105.15 105.15 0 0 0 81.47 0a72.06 72.06 0 0 0-3.36 6.83 97.68 97.68 0 0 0-29.11 0A72.37 72.37 0 0 0 45.64 0a105.89 105.89 0 0 0-26.25 8.09C2.79 32.65-1.71 56.6.54 80.21a105.73 105.73 0 0 0 32.17 16.15 77.7 77.7 0 0 0 6.89-11.11 68.42 68.42 0 0 1-10.85-5.18c.91-.66 1.8-1.34 2.66-2a75.57 75.57 0 0 0 64.32 0c.87.71 1.76 1.39 2.66 2a68.68 68.68 0 0 1-10.87 5.19 77 77 0 0 0 6.89 11.1 105.25 105.25 0 0 0 32.19-16.14c2.64-27.38-4.51-51.11-18.9-72.15ZM42.45 65.69C36.18 65.69 31 60 31 53s5-12.74 11.43-12.74S54 46 53.89 53s-5.05 12.69-11.44 12.69Zm42.24 0C78.41 65.69 73.25 60 73.25 53s5-12.74 11.44-12.74S96.23 46 96.12 53s-5.04 12.69-11.43 12.69Z"
            />
          </svg>
          <p class="font-medium text-zinc-100 text-sm">
            {{ loadingProvider === 'discord' ? 'Redirecting...' : 'Continue with Discord' }}
          </p>
        </button>

        <!-- Trackmania / Ubisoft -->
        <button
          type="button"
          :disabled="loadingProvider !== null"
          class="cursor-pointer flex justify-center items-center gap-3 rounded-lg bg-white px-4 py-2 transition hover:ring-offset-surface hover:ring-offset-2 hover:ring-2 hover:ring-primary disabled:opacity-50 disabled:cursor-not-allowed"
          @click="loginWith('trackmania')"
        >
          <svg
            version="1.1"
            id="Layer_1"
            xmlns="http://www.w3.org/2000/svg"
            xmlns:xlink="http://www.w3.org/1999/xlink"
            x="0px"
            y="0px"
            class="text-background size-6"
            viewBox="0 0 56.6 58.8"
            style="enable-background: new 0 0 56.6 58.8"
            xml:space="preserve"
          >
            <g>
              <path
                d="M56.6,29.4C56-0.8,16-11.9,0.6,16.2c0.7,0.6,1.7,1.3,2.2,1.7c-1.1,2.4-2,5-2.4,7.4C0.2,27,0,28.7,0,30.5   c0,15.6,12.7,28.3,28.3,28.3s28.3-12.7,28.3-28.3C56.6,30.1,56.6,29.8,56.6,29.4L56.6,29.4z M7.1,34.4c-0.4,3-0.2,4.1-0.2,4.5   l-0.7,0.2C6,38.5,5.2,36.8,5,34.4c-0.7-9.1,5.4-17.3,14.9-18.8c8.8-1.3,16.9,4.1,19,11.7l-0.7,0.2c-0.2-0.2-0.6-0.7-1.9-2.2   C25.9,14.7,9.3,19.5,7.1,34.4L7.1,34.4z M33.9,39.5c-1.5,2-3.7,3.4-6.3,3.4c-4.3,0-7.8-3.5-7.8-7.8c0-4.1,3.2-7.4,7.3-7.6l0,0   c2.4-0.2,4.8,1.3,6,3.4c1.1,2.4,0.7,5.2-1.1,7.3C32.6,38.5,33.3,39.1,33.9,39.5L33.9,39.5z M50.1,39.8C46,49.1,37.6,54,28.7,53.8   C11.4,52.9,6.3,33.1,17.9,25.9l0.6,0.6c-0.2,0.2-0.9,0.7-1.9,3c-1.3,2.6-1.7,5-1.5,6.7c0.9,14,20.3,16.8,27,3   c8.4-19-14.2-38.2-34.3-23.5l-0.4-0.6c5.2-8.2,15.5-11.9,25.3-9.5C47.9,9.5,56,25.1,50.1,39.8z"
              ></path>
            </g>
          </svg>
          <p class="font-medium text-background text-sm">
            {{ loadingProvider === 'trackmania' ? 'Redirecting...' : 'Continue with Ubisoft' }}
          </p>
        </button>
      </div>

      <div class="mt-4 flex gap-x-1 justify-center items-center text-on-surface-variant">
        <ShieldCheck class="size-4" />
        <p class="text-sm">End-to-end encrypted vault access</p>
      </div>
    </div>
  </div>
</template>
