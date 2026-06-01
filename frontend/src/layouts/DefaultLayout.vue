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
  <div class="relative flex min-h-screen flex-col overflow-hidden bg-black">
    <header class="flex justify-center fixed w-full z-20 bg-black/60 h-16 backdrop-blur-md sm:px-8">
      <nav class="mx-auto flex w-full max-w-7xl items-center justify-between gap-6">
        <ul class="hidden items-center gap-6 sm:flex">
          <li>
            <RouterLink
              to="/dashboard"
              class="text-zinc-400 no-underline transition-colors hover:text-white"
              active-class="text-white"
            >
              Dashboard
            </RouterLink>
          </li>
          <li>
            <RouterLink
              to="/folders"
              class="text-zinc-400 no-underline transition-colors hover:text-white"
              active-class="text-white"
            >
              My folders
            </RouterLink>
          </li>
        </ul>

        <RouterLink to="/" class="text-xl font-black tracking-tight text-white no-underline">
          Sign<span class="text-emerald-400">Vault</span>
        </RouterLink>

        <div v-if="auth.user" class="flex items-center gap-3">
          <div class="h-8 w-8 overflow-hidden rounded-full bg-zinc-600">
            <img
              v-if="auth.user.discord_avatar"
              :src="auth.user.discord_avatar"
              :alt="auth.user.discord_username"
              class="h-full w-full object-cover"
            />
          </div>
          <span class="hidden text-sm text-white sm:inline">
            {{ auth.user.discord_global_name || auth.user.discord_username }}
          </span>
          <button
            type="button"
            class="cursor-pointer rounded-lg border border-white/20 bg-transparent px-3 py-1.5 text-xs text-zinc-400 transition-colors hover:border-emerald-400 hover:text-white"
            @click="handleLogout"
          >
            Logout
          </button>
        </div>
      </nav>
    </header>

    <main class="relative z-10 flex-1 mt-16 px-6 py-8 sm:px-8">
      <div class="mx-auto max-w-7xl">
        <RouterView />
      </div>
    </main>

    <footer
      class="relative z-10 border-t border-border px-6 py-4 text-center text-xs text-zinc-400 sm:px-8"
    >
      SignVault &mdash; Trackmania sign library
    </footer>
  </div>
</template>
