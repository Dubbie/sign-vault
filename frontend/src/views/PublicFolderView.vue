<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import {
  getPublicFolder,
  getPublicFolderErrorMessage,
  unlockPublicFolder,
} from '@/lib/public-folders'
import type { PublicFolder, PublicFolderContentsResponse, PublicSign } from '@/types/public-folder'
import { useAuthStore } from '@/stores/auth'
import { useFoldersStore } from '@/stores/folders'

import UiErrorBanner from '@/components/ui/UiErrorBanner.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiFormField from '@/components/ui/UiFormField.vue'
import UiInput from '@/components/ui/UiInput.vue'
import SignGrid from '@/components/signs/SignGrid.vue'

const route = useRoute()
const authStore = useAuthStore()
const foldersStore = useFoldersStore()

const folderSlug = computed(() => String(route.params.slug))

async function checkIsAuthor() {
  if (!authStore.isAuthenticated || !folder.value) return false

  if (foldersStore.folders.length === 0) {
    await foldersStore.fetchFolders()
  }

  return foldersStore.folders.some((f) => f.public_slug === folder.value!.slug)
}

const folder = ref<PublicFolder | null>(null)
const signs = ref<PublicSign[]>([])
const requiresPassword = ref(false)
const isLoading = ref(false)
const isUnlocking = ref(false)
const isAuthor = ref(false)
const error = ref<string | null>(null)
const copiedSignId = ref<number | null>(null)

const unlockForm = reactive({
  password: '',
})

function visibilityLabel(visibility: string) {
  return visibility.charAt(0).toUpperCase() + visibility.slice(1)
}

function normalizeFolderResponse(response: PublicFolderContentsResponse) {
  folder.value = response.folder
  signs.value = response.signs
  requiresPassword.value = false
  error.value = null
  document.title = `${response.folder.name} — SignVault`
  checkIsAuthor().then((result) => {
    isAuthor.value = result
  })
}

function resetStateForPasswordPrompt() {
  folder.value = null
  signs.value = []
  requiresPassword.value = true
}

function clearUnlockForm() {
  unlockForm.password = ''
}

async function loadPublicFolder() {
  isLoading.value = true
  error.value = null

  try {
    const response = await getPublicFolder(folderSlug.value)

    if ('requires_password' in response) {
      resetStateForPasswordPrompt()
      return
    }

    normalizeFolderResponse(response)
  } catch (exception) {
    const axiosError = exception as { response?: { status?: number } }

    if (axiosError.response?.status === 404) {
      error.value = 'Folder not found.'
      folder.value = null
      signs.value = []
      requiresPassword.value = false
      isAuthor.value = false
      return
    }

    error.value = getPublicFolderErrorMessage(exception)
  } finally {
    isLoading.value = false
  }
}

async function handleUnlock() {
  error.value = null
  isUnlocking.value = true

  try {
    const response = await unlockPublicFolder(folderSlug.value, {
      password: unlockForm.password,
    })

    normalizeFolderResponse(response)
    clearUnlockForm()
  } catch (exception) {
    const axiosError = exception as { response?: { status?: number } }

    if (axiosError.response?.status === 404) {
      error.value = 'Folder not found.'
      folder.value = null
      signs.value = []
      requiresPassword.value = false
      isAuthor.value = false
      return
    }

    error.value = getPublicFolderErrorMessage(exception)
  } finally {
    isUnlocking.value = false
  }
}

async function handleCopy(signId: number) {
  const sign = signs.value.find((s) => s.id === signId)
  if (!sign) return

  error.value = null

  try {
    await navigator.clipboard.writeText(sign.public_url)
    copiedSignId.value = signId
    window.setTimeout(() => {
      if (copiedSignId.value === signId) copiedSignId.value = null
    }, 1000)
  } catch {
    error.value = 'Could not copy the sign URL. Please copy it manually.'
  }
}

onMounted(loadPublicFolder)

watch(folderSlug, () => {
  clearUnlockForm()
  folder.value = null
  signs.value = []
  requiresPassword.value = false
  isAuthor.value = false
  copiedSignId.value = null
  void loadPublicFolder()
})
</script>

<template>
  <div>
    <p v-if="isLoading && !folder && !requiresPassword" class="mt-4 text-zinc-400">
      Loading folder...
    </p>

    <UiErrorBanner v-if="error">
      {{ error }}
    </UiErrorBanner>

    <div v-if="requiresPassword" class="mx-auto max-w-md mt-12 text-center">
      <svg
        class="mx-auto size-10 text-muted mb-4"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1.5"
          d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"
        />
      </svg>

      <p
        class="mb-1 inline-block rounded-full border border-border px-3 py-0.5 text-xs font-semibold text-muted"
      >
        Password required
      </p>

      <h2 class="mt-3 text-xl text-heading">This folder is protected</h2>
      <p class="mt-1 text-sm text-muted">Enter the password to view the contents.</p>

      <form class="mt-6 text-left" @submit.prevent="handleUnlock">
        <UiFormField label="Password" name="password">
          <UiInput
            v-model="unlockForm.password"
            type="password"
            name="password"
            autocomplete="current-password"
            required
          />
        </UiFormField>

        <div class="mt-4">
          <UiButton variant="primary" type="submit" full-width :disabled="isUnlocking">
            {{ isUnlocking ? 'Unlocking...' : 'Unlock folder' }}
          </UiButton>
        </div>
      </form>
    </div>

    <div v-else-if="folder" class="grid gap-5">
      <header class="flex items-start justify-between gap-4 max-sm:flex-col">
        <div>
          <h2 class="text-[1.35rem] text-zinc-100 font-semibold">{{ folder.name }}</h2>
          <p class="text-xs text-zinc-400 font-mono">{{ folder.slug }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <div class="text-right">
            <UiBadge :label="visibilityLabel(folder.visibility)" />
            <p class="text-xs font-mono mt-1 text-zinc-400">{{ signs.length }} total</p>
          </div>
          <RouterLink v-if="isAuthor" :to="`/folders/${folder.id}`">
            <UiButton variant="primary" type="button"> Manage folder </UiButton>
          </RouterLink>
        </div>
      </header>

      <div>
        <p v-if="signs.length === 0" class="mt-4 text-zinc-400">No signs available.</p>

        <SignGrid
          v-else
          :signs="signs"
          :copied-sign-id="copiedSignId"
          :selectable="false"
          @copy="handleCopy"
        />
      </div>
    </div>
  </div>
</template>
