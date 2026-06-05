<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'

import logoUrl from '@/assets/logo.svg'
import CookieDisclaimer from '@/components/ui/CookieDisclaimer.vue'
import UiDropdown from '@/components/ui/UiDropdown.vue'
import { useAuthStore } from '@/stores/auth'
import { LogOut, ShieldAlert } from '@lucide/vue'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const showUserMenu = ref(false)
const exploreRouteNames = new Set(['explore', 'public-folder'])

function navClass(active: boolean) {
  return [
    'no-underline font-medium transition-colors border-b-2 py-component-padding-y',
    active
      ? 'text-primary border-primary'
      : 'border-transparent text-on-surface-variant hover:text-on-surface ',
  ]
}

async function handleLogout() {
  await auth.logout()
  await router.replace({ name: 'explore' })
}

async function handleLogin() {
  await auth.loginWithDiscord()
}

function isExploreActive() {
  return exploreRouteNames.has(String(route.name ?? ''))
}
</script>

<template>
  <div class="relative flex min-h-screen flex-col overflow-hidden bg-background">
    <header
      class="fixed top-0 w-full z-50 flex justify-between items-center px-container-margin h-16 bg-surface-dim/80 backdrop-blur-xl border-b border-outline-variant/20"
    >
      <nav class="flex gap-8 mx-auto w-full max-w-7xl items-center">
        <div class="flex mb-1">
          <RouterLink to="/" class="flex items-center gap-x-2">
            <img :src="logoUrl" alt="SignVault logo" class="size-8 mt-1" />
            <p class="text-[24px] font-medium text-zinc-100 no-underline">
              Sign<span class="text-emerald-400 font-bold">Vault</span>
            </p>
          </RouterLink>
        </div>

        <ul class="hidden items-center gap-4 sm:flex sm:flex-1">
          <li v-if="auth.user">
            <RouterLink to="/dashboard" :class="navClass(route.name === 'dashboard')">
              Dashboard
            </RouterLink>
          </li>
          <li>
            <RouterLink to="/" :class="navClass(isExploreActive())"> Explore </RouterLink>
          </li>
          <li v-if="auth.user">
            <RouterLink to="/folders" :class="navClass(route.path.startsWith('/folders'))">
              My folders
            </RouterLink>
          </li>
          <li>
            <RouterLink to="/utilities" :class="navClass(route.path.startsWith('/utilities'))">
              Utilities
            </RouterLink>
          </li>

          <li v-if="auth.isAdmin">
            <RouterLink
              to="/admin/users"
              class="no-underline font-medium transition-colors border-b-2 py-component-padding-y flex items-center gap-1.5"
              :class="
                route.path.startsWith('/admin')
                  ? 'text-red-400 border-red-400'
                  : 'border-transparent text-red-400/60 hover:text-red-400'
              "
            >
              <ShieldAlert class="size-3.5" />
              Admin
            </RouterLink>
          </li>
        </ul>

        <div class="flex items-center justify-end gap-x-8">
          <div v-if="auth.user" class="flex items-center gap-3">
            <!-- TODO: Add global search <div class="relative hidden sm:block opacity-50">
              <Search
                stroke-width="3"
                class="size-4 absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant"
              />

              <input
                type="text"
                class="bg-surface-container-low border border-outline-variant/30 rounded-lg pl-10 pr-4 py-1.5 focus:outline-none focus:border-primary transition-all w-64"
                placeholder="Search folders..."
                disabled
              />
            </div> -->

            <UiDropdown v-model="showUserMenu" placement="bottom-end" trigger-class="inline-flex">
              <template #trigger="{ toggle }">
                <button
                  class="cursor-pointer ring-offset-2 ring-transparent rounded-lg transition-all ring-offset-background hover:ring-primary hover:ring-2"
                  type="button"
                  aria-label="Open user menu"
                  @click="toggle"
                >
                  <div class="size-9 overflow-hidden rounded-lg bg-zinc-600">
                    <img
                      v-if="auth.user.discord_avatar"
                      :src="auth.user.discord_avatar"
                      :alt="auth.user.discord_username"
                      class="h-full w-full object-cover"
                    />
                  </div>
                </button>
              </template>

              <template #default="{ close }">
                <div>
                  <button
                    type="button"
                    class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-red-400 transition hover:bg-red-500/10"
                    @click="(close(), void handleLogout())"
                  >
                    <LogOut class="size-4" />
                    Logout
                  </button>
                </div>
              </template>
            </UiDropdown>
          </div>

          <button
            v-else
            type="button"
            class="rounded-md bg-emerald-400 px-4 py-1.5 text-sm font-semibold text-background no-underline transition hover:bg-emerald-200"
            @click="handleLogin"
          >
            {{ auth.isLoading ? 'Redirecting...' : 'Login with Discord' }}
          </button>
        </div>
      </nav>
    </header>

    <main class="relative z-10 flex-1 mt-16 px-6 py-8 sm:px-8">
      <div class="mx-auto max-w-7xl">
        <RouterView />
      </div>
    </main>

    <CookieDisclaimer />

    <footer
      class="w-full py-section-gap px-container-margin bg-surface-container-lowest border-t border-outline-variant/10"
    >
      <div class="max-w-7xl mx-auto">
        <div class="w-full flex items-center justify-center gap-4">
          <div class="flex flex-col flex-1 gap-1">
            <span class="text-headline-md font-bold text-on-surface">SignVault</span>
            <span class="text-label-sm text-on-surface-variant/80">Trackmania sign library</span>
          </div>

          <div class="flex gap-8">
            <RouterLink
              to="/terms"
              class="text-label-sm text-on-surface-variant hover:text-primary transition-colors opacity-80 hover:opacity-100"
            >
              Terms of Service
            </RouterLink>
            <RouterLink
              to="/privacy"
              class="text-label-sm text-on-surface-variant hover:text-primary transition-colors opacity-80 hover:opacity-100"
            >
              Privacy Policy
            </RouterLink>
            <a
              href="https://github.com/Dubbie/sign-vault"
              target="_blank"
              rel="noopener noreferrer"
              class="text-label-sm text-on-surface-variant hover:text-primary transition-colors opacity-80 hover:opacity-100"
            >
              Source Code
            </a>
            <a
              href="https://discord.gg/vkaXfkr4qa"
              target="_blank"
              rel="noopener noreferrer"
              class="text-label-sm text-on-surface-variant hover:text-primary transition-colors opacity-80 hover:opacity-100"
            >
              Discord
            </a>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>
