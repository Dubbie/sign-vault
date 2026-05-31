<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

import {
  getPublicFolder,
  getPublicFolderErrorMessage,
  unlockPublicFolder,
} from '@/lib/public-folders'
import type {
  PublicFolder,
  PublicFolderContentsResponse,
  PublicSign,
} from '@/types/public-folder'

import UiCard from '@/components/ui/UiCard.vue'
import UiEyebrow from '@/components/ui/UiEyebrow.vue'
import UiErrorBanner from '@/components/ui/UiErrorBanner.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiPanel from '@/components/ui/UiPanel.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiFormField from '@/components/ui/UiFormField.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSignCard from '@/components/ui/UiSignCard.vue'

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
    }, 1500)
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
  <UiCard max-width="72rem">
    <UiEyebrow>Public folder</UiEyebrow>
    <h1 class="text-[clamp(2rem,4vw,2.5rem)] leading-tight text-heading">Shared folder</h1>

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

    <div v-else-if="folder" class="mt-2 grid gap-5">
      <header class="flex items-start justify-between gap-4 max-sm:flex-col">
        <div>
          <h2 class="text-[1.35rem] text-heading">{{ folder.name }}</h2>
          <p class="text-text-muted">{{ folder.slug }}</p>
        </div>
        <UiBadge :label="visibilityLabel(folder.visibility)" />
      </header>

      <UiPanel>
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="mb-1 text-[0.85rem] font-semibold uppercase tracking-[0.14em] text-primary">
              Signs
            </p>
            <h3 class="text-[1.35rem] text-heading">Available signs</h3>
          </div>
          <p class="text-text-muted">{{ signs.length }} total</p>
        </div>

        <p v-if="signs.length === 0" class="mt-4 text-text-muted">No signs available.</p>

        <div v-else class="mt-4 grid gap-4">
          <UiSignCard
            v-for="sign in signs"
            :key="sign.id"
            :id="sign.id"
            :name="sign.name"
            :public-url="sign.public_url"
            :mime-type="sign.mime_type"
            :width="sign.width"
            :height="sign.height"
            :copied="copiedSignId === sign.id"
            @copy="handleCopy"
          />
        </div>
      </UiPanel>
    </div>
  </UiCard>
</template>
