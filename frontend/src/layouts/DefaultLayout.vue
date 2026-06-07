<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'

import logoUrl from '@/assets/logo.svg'
import CookieDisclaimer from '@/components/ui/CookieDisclaimer.vue'
import DiscordBanner from '@/components/ui/DiscordBanner.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiDropdown from '@/components/ui/UiDropdown.vue'
import UiModal from '@/components/ui/UiModal.vue'
import { renderReleaseNotesMarkdown } from '@/lib/release-notes'
import { useAuthStore } from '@/stores/auth'
import { LogOut, Menu, Settings, ShieldAlert, X } from '@lucide/vue'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const showUserMenu = ref(false)
const showMobileNav = ref(false)
const showReleaseNotes = ref(false)
const exploreRouteNames = new Set(['explore', 'public-folder'])
const releaseVersion = __APP_VERSION__
const releaseDate = __APP_RELEASE_DATE__
const releaseNotes = __APP_RELEASE_NOTES__
const releaseNotesBody = computed(() => renderReleaseNotesMarkdown(releaseNotes))

function navClass(active: boolean) {
  return [
    'no-underline font-medium transition-colors border-b-2 py-component-padding-y',
    active
      ? 'text-primary border-primary'
      : 'border-transparent text-on-surface-variant hover:text-on-surface ',
  ]
}

function mobileNavClass(active: boolean) {
  return [
    'rounded-lg px-3 py-2.5 font-medium no-underline transition-colors',
    active
      ? 'bg-primary/10 text-primary'
      : 'text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface',
  ]
}

function onMobileNavKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') showMobileNav.value = false
}

watch(showMobileNav, (open) => {
  if (open) {
    document.addEventListener('keydown', onMobileNavKeydown)
    document.body.style.overflow = 'hidden'
  } else {
    document.removeEventListener('keydown', onMobileNavKeydown)
    document.body.style.overflow = ''
  }
})

watch(
  () => route.fullPath,
  () => {
    showMobileNav.value = false
  },
)

onUnmounted(() => {
  document.removeEventListener('keydown', onMobileNavKeydown)
  document.body.style.overflow = ''
})

async function handleLogout() {
  await auth.logout()
  await router.replace({ name: 'explore' })
}

function handleLogin() {
  router.push({ name: 'login' })
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

        <div class="flex items-center justify-end gap-x-8 ml-auto">
          <a
            href="https://discord.gg/vkaXfkr4qa"
            target="_blank"
            rel="noopener noreferrer"
            aria-label="Join our Discord"
            class="text-on-surface-variant/60 transition-colors hover:text-indigo-400"
          >
            <svg
              class="size-5 fill-current"
              viewBox="0 0 127.14 96.36"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                d="M107.7 8.07A105.15 105.15 0 0 0 81.47 0a72.06 72.06 0 0 0-3.36 6.83 97.68 97.68 0 0 0-29.11 0A72.37 72.37 0 0 0 45.64 0a105.89 105.89 0 0 0-26.25 8.09C2.79 32.65-1.71 56.6.54 80.21a105.73 105.73 0 0 0 32.17 16.15 77.7 77.7 0 0 0 6.89-11.11 68.42 68.42 0 0 1-10.85-5.18c.91-.66 1.8-1.34 2.66-2a75.57 75.57 0 0 0 64.32 0c.87.71 1.76 1.39 2.66 2a68.68 68.68 0 0 1-10.87 5.19 77 77 0 0 0 6.89 11.1 105.25 105.25 0 0 0 32.19-16.14c2.64-27.38-4.51-51.11-18.9-72.15ZM42.45 65.69C36.18 65.69 31 60 31 53s5-12.74 11.43-12.74S54 46 53.89 53s-5.05 12.69-11.44 12.69Zm42.24 0C78.41 65.69 73.25 60 73.25 53s5-12.74 11.44-12.74S96.23 46 96.12 53s-5.04 12.69-11.43 12.69Z"
              />
            </svg>
          </a>

          <div v-if="auth.user" class="hidden items-center gap-3 sm:flex">
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
                      v-if="auth.user.avatar_url"
                      :src="auth.user.avatar_url"
                      :alt="auth.user.display_name"
                      class="h-full w-full object-cover"
                    />
                  </div>
                </button>
              </template>

              <template #default="{ close }">
                <div>
                  <RouterLink
                    to="/settings"
                    class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-zinc-300 transition hover:bg-zinc-500/10"
                    @click="close()"
                  >
                    <Settings class="size-4" />
                    Settings
                  </RouterLink>
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

          <div v-else class="hidden sm:block">
            <UiButton @click="handleLogin"> Login </UiButton>
          </div>

          <button
            type="button"
            class="sm:hidden cursor-pointer text-on-surface-variant transition-colors hover:text-on-surface"
            aria-label="Open navigation menu"
            @click="showMobileNav = true"
          >
            <Menu class="size-6" />
          </button>
        </div>
      </nav>
    </header>

    <main class="relative z-10 flex-1 mt-16">
      <DiscordBanner />
      <div class="px-6 py-8 sm:px-8">
        <div class="mx-auto max-w-7xl">
          <RouterView />
        </div>
      </div>
    </main>

    <CookieDisclaimer />

    <footer
      class="w-full py-section-gap px-container-margin bg-surface-container-lowest border-t border-outline-variant/10"
    >
      <div class="max-w-7xl mx-auto">
        <div
          class="w-full flex flex-col items-center gap-6 text-center md:flex-row md:items-center md:justify-center md:text-left"
        >
          <div class="flex flex-col items-center gap-1 md:flex-1 md:items-start">
            <span class="text-headline-md font-bold text-on-surface">SignVault</span>
            <span class="text-label-sm text-on-surface-variant/80">Trackmania sign library</span>
          </div>

          <div
            class="flex flex-wrap items-center justify-center gap-x-6 gap-y-3 md:justify-start md:gap-x-8"
          >
            <button
              type="button"
              class="font-mono cursor-pointer rounded-full border border-outline-variant/60 bg-surface-container px-2 py-0.5 text-xs text-on-surface transition hover:border-primary hover:text-primary"
              @click="showReleaseNotes = true"
            >
              v{{ releaseVersion }}
            </button>
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

    <Teleport to="body">
      <Transition name="mobile-nav-backdrop">
        <div
          v-if="showMobileNav"
          class="fixed inset-0 z-50 bg-background/80 backdrop-blur sm:hidden"
          @click.self="showMobileNav = false"
        >
          <Transition name="mobile-nav-panel">
            <nav
              v-if="showMobileNav"
              class="fixed top-0 right-0 bottom-0 w-72 max-w-[85vw] bg-surface-dim border-l border-outline-variant/20 shadow-2xl flex flex-col overflow-y-auto"
            >
              <div
                class="flex items-center justify-between px-container-margin h-16 border-b border-outline-variant/10"
              >
                <span class="text-label-md text-on-surface-variant">Menu</span>
                <button
                  type="button"
                  aria-label="Close navigation menu"
                  class="cursor-pointer text-on-surface-variant hover:text-on-surface"
                  @click="showMobileNav = false"
                >
                  <X class="size-5" />
                </button>
              </div>

              <ul class="flex flex-col gap-1 p-3">
                <li v-if="auth.user">
                  <RouterLink
                    to="/dashboard"
                    :class="mobileNavClass(route.name === 'dashboard')"
                    @click="showMobileNav = false"
                  >
                    Dashboard
                  </RouterLink>
                </li>
                <li>
                  <RouterLink
                    to="/"
                    :class="mobileNavClass(isExploreActive())"
                    @click="showMobileNav = false"
                  >
                    Explore
                  </RouterLink>
                </li>
                <li v-if="auth.user">
                  <RouterLink
                    to="/folders"
                    :class="mobileNavClass(route.path.startsWith('/folders'))"
                    @click="showMobileNav = false"
                  >
                    My folders
                  </RouterLink>
                </li>
                <li>
                  <RouterLink
                    to="/utilities"
                    :class="mobileNavClass(route.path.startsWith('/utilities'))"
                    @click="showMobileNav = false"
                  >
                    Utilities
                  </RouterLink>
                </li>
                <li v-if="auth.isAdmin">
                  <RouterLink
                    to="/admin/users"
                    class="flex items-center gap-2 rounded-lg px-3 py-2.5 font-medium no-underline transition-colors"
                    :class="
                      route.path.startsWith('/admin')
                        ? 'bg-red-400/10 text-red-400'
                        : 'text-red-400/60 hover:bg-red-400/5 hover:text-red-400'
                    "
                    @click="showMobileNav = false"
                  >
                    <ShieldAlert class="size-4" />
                    Admin
                  </RouterLink>
                </li>
              </ul>

              <div class="mt-auto border-t border-outline-variant/10 p-3">
                <template v-if="auth.user">
                  <RouterLink
                    to="/settings"
                    class="flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm text-on-surface-variant transition hover:bg-surface-container-low"
                    @click="showMobileNav = false"
                  >
                    <Settings class="size-4" />
                    Settings
                  </RouterLink>
                  <button
                    type="button"
                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-left text-sm text-red-400 transition hover:bg-red-500/10"
                    @click="((showMobileNav = false), void handleLogout())"
                  >
                    <LogOut class="size-4" />
                    Logout
                  </button>
                </template>
                <UiButton v-else class="w-full" @click="((showMobileNav = false), handleLogin())">
                  Login
                </UiButton>
              </div>
            </nav>
          </Transition>
        </div>
      </Transition>
    </Teleport>

    <UiModal v-model="showReleaseNotes" :title="`Release notes · v${releaseVersion}`" size="lg">
      <div class="space-y-4">
        <p class="text-label-sm uppercase tracking-[0.18em] text-on-surface-variant/80">
          {{ releaseDate }}
        </p>
        <div
          class="release-notes-content max-h-[60vh] overflow-y-auto rounded-xl border border-outline-variant/30 bg-surface-container-low p-4 text-sm leading-6 text-on-surface-variant"
          v-html="releaseNotesBody"
        ></div>
      </div>
    </UiModal>
  </div>
</template>

<style scoped>
.mobile-nav-backdrop-enter-active,
.mobile-nav-backdrop-leave-active {
  transition: opacity 0.2s ease;
}

.mobile-nav-backdrop-enter-from,
.mobile-nav-backdrop-leave-to {
  opacity: 0;
}

.mobile-nav-panel-enter-active {
  transition: transform 0.25s ease-out;
}

.mobile-nav-panel-leave-active {
  transition: transform 0.2s ease-in;
}

.mobile-nav-panel-enter-from,
.mobile-nav-panel-leave-to {
  transform: translateX(100%);
}

:deep(.release-notes-content h2),
:deep(.release-notes-content h3) {
  margin-top: 1rem;
  margin-bottom: 0.5rem;
  color: var(--color-on-surface);
  font-weight: 700;
}

:deep(.release-notes-content h2:first-child),
:deep(.release-notes-content h3:first-child) {
  margin-top: 0;
}

:deep(.release-notes-content h2) {
  font-size: 1.1rem;
}

:deep(.release-notes-content h3) {
  font-size: 1rem;
}

:deep(.release-notes-content p) {
  margin-top: 0.75rem;
}

:deep(.release-notes-content p:first-child) {
  margin-top: 0;
}

:deep(.release-notes-content ul) {
  margin-top: 0.75rem;
  padding-left: 1.25rem;
  list-style: disc;
}

:deep(.release-notes-content li + li) {
  margin-top: 0.5rem;
}

:deep(.release-notes-content a) {
  color: var(--color-primary);
  text-decoration: underline;
  text-underline-offset: 0.18em;
}

:deep(.release-notes-content a:hover) {
  color: var(--color-primary-fixed);
}

:deep(.release-notes-content code) {
  padding: 0.125rem 0.375rem;
  border-radius: 999px;
  background: color-mix(in srgb, var(--color-surface-container-high) 75%, transparent);
  color: var(--color-on-surface);
  font-size: 0.85em;
}
</style>
