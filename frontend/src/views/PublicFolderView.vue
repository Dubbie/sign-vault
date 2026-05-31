<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

import {
  getPublicFolder,
  getPublicFolderErrorMessage,
  unlockPublicFolder,
} from '@/lib/public-folders'
import type { PublicFolder, PublicFolderContentsResponse, PublicSign } from '@/types/public-folder'

import UiErrorBanner from '@/components/ui/UiErrorBanner.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiPanel from '@/components/ui/UiPanel.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiFormField from '@/components/ui/UiFormField.vue'
import UiInput from '@/components/ui/UiInput.vue'
import PublicSignGrid from '@/components/signs/PublicSignGrid.vue'

const route = useRoute()

const folderSlug = computed(() => String(route.params.slug))

const folder = ref<PublicFolder | null>(null)
const signs = ref<PublicSign[]>([])
const requiresPassword = ref(false)
const isLoading = ref(false)
const isUnlocking = ref(false)
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
  copiedSignId.value = null
  void loadPublicFolder()
})
</script>

<template>
  <div>
    <p v-if="isLoading && !folder && !requiresPassword" class="mt-4 text-text-muted">
      Loading folder...
    </p>

    <UiErrorBanner v-if="error">
      {{ error }}
    </UiErrorBanner>

    <UiPanel v-if="requiresPassword">
      <div class="flex items-start justify-between gap-4">
        <div>
          <p class="mb-1 text-[0.85rem] font-semibold uppercase tracking-[0.14em] text-primary">
            Password required
          </p>
          <h2 class="text-[1.35rem] text-heading">This folder is protected</h2>
        </div>
      </div>

      <form class="mt-4 grid gap-4" @submit.prevent="handleUnlock">
        <UiFormField label="Password" name="password">
          <UiInput
            v-model="unlockForm.password"
            type="password"
            name="password"
            autocomplete="current-password"
            required
          />
        </UiFormField>

        <div class="flex gap-3">
          <UiButton variant="primary" type="submit" :disabled="isUnlocking">
            {{ isUnlocking ? 'Unlocking...' : 'Unlock folder' }}
          </UiButton>
        </div>
      </form>
    </UiPanel>

    <div v-else-if="folder" class="grid gap-5">
      <header class="flex items-start justify-between gap-4 max-sm:flex-col">
        <div>
          <h2 class="text-[1.35rem] text-white font-semibold">{{ folder.name }}</h2>
          <p class="text-xs text-zinc-400 font-mono">{{ folder.slug }}</p>
        </div>
        <div class="text-right">
          <UiBadge :label="visibilityLabel(folder.visibility)" />
          <p class="text-xs font-mono pr-2 mt-2 text-zinc-400">{{ signs.length }} total</p>
        </div>
      </header>

      <div>
        <p v-if="signs.length === 0" class="mt-4 text-zinc-400">No signs available.</p>

        <PublicSignGrid v-else :signs="signs" :copied-sign-id="copiedSignId" @copy="handleCopy" />
      </div>
    </div>
  </div>
</template>
