<script setup lang="ts">
import { ref } from 'vue'
import { X } from '@lucide/vue'

const STORAGE_KEY = 'discord-banner-dismissed'

const visible = ref(localStorage.getItem(STORAGE_KEY) !== 'true')

function dismiss() {
  visible.value = false
  localStorage.setItem(STORAGE_KEY, 'true')
}
</script>

<template>
  <Transition name="banner">
    <div v-if="visible" class="relative z-40 w-full bg-indigo-500/10 px-4 py-2.5">
      <div class="mx-auto flex max-w-7xl items-center justify-between gap-4">
        <div class="flex items-center gap-3 text-sm text-indigo-200">
          <svg
            class="size-4 shrink-0 fill-indigo-400"
            viewBox="0 0 127.14 96.36"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              d="M107.7 8.07A105.15 105.15 0 0 0 81.47 0a72.06 72.06 0 0 0-3.36 6.83 97.68 97.68 0 0 0-29.11 0A72.37 72.37 0 0 0 45.64 0a105.89 105.89 0 0 0-26.25 8.09C2.79 32.65-1.71 56.6.54 80.21a105.73 105.73 0 0 0 32.17 16.15 77.7 77.7 0 0 0 6.89-11.11 68.42 68.42 0 0 1-10.85-5.18c.91-.66 1.8-1.34 2.66-2a75.57 75.57 0 0 0 64.32 0c.87.71 1.76 1.39 2.66 2a68.68 68.68 0 0 1-10.87 5.19 77 77 0 0 0 6.89 11.1 105.25 105.25 0 0 0 32.19-16.14c2.64-27.38-4.51-51.11-18.9-72.15ZM42.45 65.69C36.18 65.69 31 60 31 53s5-12.74 11.43-12.74S54 46 53.89 53s-5.05 12.69-11.44 12.69Zm42.24 0C78.41 65.69 73.25 60 73.25 53s5-12.74 11.44-12.74S96.23 46 96.12 53s-5.04 12.69-11.43 12.69Z"
            />
          </svg>
          <span>
            Found a bug or have a suggestion?
            <a
              href="https://discord.gg/vkaXfkr4qa"
              target="_blank"
              rel="noopener noreferrer"
              class="font-semibold text-indigo-300 underline underline-offset-2 hover:text-white transition-colors"
            >
              Join our Discord</a
            >
            and let us know.
          </span>
        </div>

        <button
          type="button"
          class="cursor-pointer shrink-0 rounded p-1 text-indigo-400 transition-colors hover:bg-indigo-500/20 hover:text-white"
          aria-label="Dismiss"
          @click="dismiss"
        >
          <X class="size-4" />
        </button>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.banner-enter-active,
.banner-leave-active {
  transition:
    opacity 0.2s ease,
    max-height 0.25s ease;
  max-height: 60px;
  overflow: hidden;
}
.banner-enter-from,
.banner-leave-to {
  opacity: 0;
  max-height: 0;
}
</style>
