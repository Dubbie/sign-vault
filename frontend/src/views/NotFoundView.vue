<script setup lang="ts">
import UiButton from '@/components/ui/UiButton.vue'
import { Home } from '@lucide/vue'
</script>

<template>
  <div
    class="mx-auto flex min-h-[65vh] max-w-3xl items-center justify-center flex-col text-center px-4"
  >
    <p class="mt-6 text-9xl font-black text-primary drop-shadow-xl drop-shadow-primary/20">404</p>

    <h1 class="mt-3 text-headline-lg text-on-surface">This page does not exist</h1>
    <p class="mx-auto mt-4 max-w-xl text-body-lg text-on-surface-variant">
      The link may be outdated, the folder may have been removed, or the address is incorrect.
    </p>

    <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
      <UiButton :to="{ name: 'explore' }">
        <Home class="size-5" />
        Explore folders
      </UiButton>

      <a href="https://discord.gg/vkaXfkr4qa" target="_blank" rel="noopener noreferrer">
        <UiButton variant="secondary">
          <svg
            class="size-5 shrink-0 fill-indigo-400"
            viewBox="0 0 127.14 96.36"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              d="M107.7 8.07A105.15 105.15 0 0 0 81.47 0a72.06 72.06 0 0 0-3.36 6.83 97.68 97.68 0 0 0-29.11 0A72.37 72.37 0 0 0 45.64 0a105.89 105.89 0 0 0-26.25 8.09C2.79 32.65-1.71 56.6.54 80.21a105.73 105.73 0 0 0 32.17 16.15 77.7 77.7 0 0 0 6.89-11.11 68.42 68.42 0 0 1-10.85-5.18c.91-.66 1.8-1.34 2.66-2a75.57 75.57 0 0 0 64.32 0c.87.71 1.76 1.39 2.66 2a68.68 68.68 0 0 1-10.87 5.19 77 77 0 0 0 6.89 11.1 105.25 105.25 0 0 0 32.19-16.14c2.64-27.38-4.51-51.11-18.9-72.15ZM42.45 65.69C36.18 65.69 31 60 31 53s5-12.74 11.43-12.74S54 46 53.89 53s-5.05 12.69-11.44 12.69Zm42.24 0C78.41 65.69 73.25 60 73.25 53s5-12.74 11.44-12.74S96.23 46 96.12 53s-5.04 12.69-11.43 12.69Z"
            />
          </svg>
          Check Discord
        </UiButton>
      </a>
    </div>
  </div>
</template>
