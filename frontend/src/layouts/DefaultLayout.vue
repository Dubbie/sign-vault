<script setup lang="ts">
import { RouterLink, RouterView, useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

async function handleLogout() {
  await auth.logout()
  await router.replace({ name: 'login' })
}
</script>

<template>
  <div class="relative flex min-h-screen flex-col overflow-hidden">
    <div
      class="pointer-events-none fixed -left-16 -top-24 h-72 w-72 rounded-full bg-ambient-left opacity-45 blur-[32px]"
    ></div>
    <div
      class="pointer-events-none fixed -bottom-32 -right-32 h-88 w-88 rounded-full bg-ambient-right opacity-45 blur-[32px]"
    ></div>

    <header
      class="relative z-10 border-b border-border bg-surface/60 px-6 py-3 backdrop-blur-md sm:px-8"
    >
      <nav class="mx-auto flex max-w-7xl items-center justify-between gap-6">
        <RouterLink to="/" class="text-xl font-bold text-heading no-underline">
          SignVault
        </RouterLink>

        <ul class="hidden items-center gap-6 sm:flex">
          <li>
            <RouterLink
              to="/dashboard"
              class="text-text-muted no-underline transition-colors hover:text-heading"
              active-class="text-heading"
            >
              Dashboard
            </RouterLink>
          </li>
          <li>
            <RouterLink
              to="/folders"
              class="text-text-muted no-underline transition-colors hover:text-heading"
              active-class="text-heading"
            >
              Folders
            </RouterLink>
          </li>
        </ul>

        <div v-if="auth.user" class="flex items-center gap-3">
          <div class="h-8 w-8 overflow-hidden rounded-full bg-surface-strong">
            <img
              v-if="auth.user.discord_avatar"
              :src="auth.user.discord_avatar"
              :alt="auth.user.discord_username"
              class="h-full w-full object-cover"
            />
          </div>
          <span class="hidden text-sm text-heading sm:inline">
            {{ auth.user.discord_global_name || auth.user.discord_username }}
          </span>
          <button
            type="button"
            class="cursor-pointer rounded-lg border border-border bg-transparent px-3 py-1.5 text-xs text-text-muted transition-colors hover:border-border-danger hover:text-danger-text"
            @click="handleLogout"
          >
            Logout
          </button>
        </div>
      </nav>
    </header>

    <main class="relative z-10 flex-1 px-6 py-8 sm:px-8">
      <div class="mx-auto max-w-7xl">
        <RouterView />
      </div>
    </main>

    <footer
      class="relative z-10 border-t border-border px-6 py-4 text-center text-xs text-text-muted sm:px-8"
    >
      SignVault &mdash; Trackmania sign library
    </footer>
  </div>
</template>
