<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import { useFoldersStore } from '@/stores/folders'
import { useSignsStore } from '@/stores/signs'
import type { CreateSignPayload } from '@/types/sign'

import UiCard from '@/components/ui/UiCard.vue'
import UiEyebrow from '@/components/ui/UiEyebrow.vue'
import UiErrorBanner from '@/components/ui/UiErrorBanner.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiPanel from '@/components/ui/UiPanel.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiFormField from '@/components/ui/UiFormField.vue'
import UiSignCard from '@/components/ui/UiSignCard.vue'

const foldersStore = useFoldersStore()
const signsStore = useSignsStore()
const route = useRoute()

const folderId = computed(() => Number(route.params.id))
const folder = computed(() => foldersStore.currentFolder)

const selectedFiles = ref<File[]>([])
const fileInput = ref<HTMLInputElement | null>(null)
const copiedSignId = ref<number | null>(null)
const copiedPublicUrl = ref(false)
const publicUrlError = ref<string | null>(null)

const allowedMimeTypes = new Set(['image/png', 'image/jpeg', 'image/webp'])
const dateFormatter = new Intl.DateTimeFormat(undefined, {
  dateStyle: 'medium',
  timeStyle: 'short',
})

function formatDate(value: string) {
  return dateFormatter.format(new Date(value))
}

function visibilityLabel(visibility: string) {
  return visibility.charAt(0).toUpperCase() + visibility.slice(1)
}

const publicFolderPath = computed(() => `/public/folders/${folder.value?.slug ?? ''}`)

function canShareFolder() {
  return folder.value?.visibility !== 'private'
}

function resetUploadForm() {
  selectedFiles.value = []
  if (fileInput.value) fileInput.value.value = ''
}

function validateSelectedFiles(files: File[]) {
  if (files.length === 0) return 'At least one image file is required.'
  const invalidFile = files.find((file) => !allowedMimeTypes.has(file.type))
  if (invalidFile) return 'Files must be PNG, JPEG, or WebP images.'
  return null
}

async function loadFolder() {
  const id = folderId.value
  if (!Number.isFinite(id)) {
    foldersStore.error = 'Invalid folder id.'
    return
  }
  const loadedFolder = await foldersStore.fetchFolder(id)
  if (loadedFolder) await signsStore.fetchFolderSigns(id)
}

onMounted(loadFolder)

watch(folderId, () => {
  foldersStore.clearCurrentFolder()
  signsStore.clearCurrentSign()
  signsStore.signs = []
  copiedSignId.value = null
  copiedPublicUrl.value = false
  publicUrlError.value = null
  void loadFolder()
})

async function handleUploadSubmit() {
  signsStore.clearError()

  const fileError = validateSelectedFiles(selectedFiles.value)
  if (fileError) {
    signsStore.error = fileError
    return
  }

  const payload: CreateSignPayload = { files: selectedFiles.value }
  const uploadedSigns = await signsStore.uploadSign(folderId.value, payload)
  if (uploadedSigns) resetUploadForm()
}

function handleFileChange(event: Event) {
  const input = event.target as HTMLInputElement
  const files = Array.from(input.files ?? [])
  signsStore.clearError()

  const fileError = validateSelectedFiles(files)
  if (fileError) {
    selectedFiles.value = []
    input.value = ''
    signsStore.error = fileError
    return
  }

  selectedFiles.value = files
}

async function handleDelete(signId: number) {
  const sign = signsStore.signs.find((s) => s.id === signId)
  if (!sign) return

  const confirmed = window.confirm(`Delete "${sign.name}"?`)
  if (!confirmed) return
  await signsStore.deleteSign(signId)
}

async function handleCopy(signId: number) {
  const sign = signsStore.signs.find((s) => s.id === signId)
  if (!sign) return

  const copied = await signsStore.copySignUrl(sign)
  if (!copied) return

  copiedSignId.value = signId
  window.setTimeout(() => {
    if (copiedSignId.value === signId) copiedSignId.value = null
  }, 1500)
}

async function handleCopyPublicUrl() {
  publicUrlError.value = null
  try {
    await navigator.clipboard.writeText(publicFolderPath.value)
    copiedPublicUrl.value = true
    window.setTimeout(() => {
      copiedPublicUrl.value = false
    }, 1500)
  } catch {
    publicUrlError.value = 'Could not copy the public URL. Please copy it manually.'
  }
}
</script>

<template>
  <UiCard max-width="72rem">
    <RouterLink
      to="/folders"
      class="text-primary underline-offset-2 hover:underline"
    >
      Back to folders
    </RouterLink>

    <UiEyebrow>Folder details</UiEyebrow>

    <UiErrorBanner v-if="foldersStore.error">
      {{ foldersStore.error }}
    </UiErrorBanner>

    <p v-if="foldersStore.isLoading && !folder" class="mt-4 text-text-muted">
      Loading folder...
    </p>

    <div v-else-if="folder" class="mt-2 grid gap-5">
      <header class="flex items-start justify-between gap-4 max-sm:flex-col">
        <div>
          <h1 class="text-[clamp(2rem,4vw,2.5rem)] leading-tight text-heading">
            {{ folder.name }}
          </h1>
          <p class="text-text-muted">{{ folder.slug }}</p>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3 max-sm:justify-start">
          <UiBadge :label="visibilityLabel(folder.visibility)" />
          <UiButton variant="secondary" type="button">
            <RouterLink
              class="text-heading no-underline"
              :to="{ name: 'folders-edit', params: { id: folder.id } }"
            >
              Edit folder
            </RouterLink>
          </UiButton>
        </div>
      </header>

      <dl class="grid grid-cols-[repeat(auto-fit,minmax(10rem,1fr))] gap-4">
        <div>
          <dt class="text-[0.8rem] uppercase tracking-[0.08em] text-text-muted">Created</dt>
          <dd class="mt-1 text-heading">{{ formatDate(folder.created_at) }}</dd>
        </div>
        <div>
          <dt class="text-[0.8rem] uppercase tracking-[0.08em] text-text-muted">Updated</dt>
          <dd class="mt-1 text-heading">{{ formatDate(folder.updated_at) }}</dd>
        </div>
      </dl>

      <UiPanel v-if="canShareFolder()">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="mb-1 text-[0.85rem] font-semibold uppercase tracking-[0.14em] text-primary">
              Public sharing
            </p>
            <h2 class="text-[1.35rem] text-heading">Share this folder</h2>
          </div>
        </div>

        <UiErrorBanner v-if="publicUrlError">
          {{ publicUrlError }}
        </UiErrorBanner>

        <div class="mt-4 flex flex-wrap gap-3">
          <UiButton variant="secondary" type="button">
            <RouterLink
              class="text-heading no-underline"
              :to="{ name: 'public-folder', params: { slug: folder.slug } }"
            >
              Open public page
            </RouterLink>
          </UiButton>
          <button
            type="button"
            class="min-w-[6.5rem] cursor-pointer rounded-xl border border-border bg-transparent px-[0.9rem] py-[0.6rem] text-heading transition duration-150 ease-in-out hover:bg-white/5"
            @click="handleCopyPublicUrl"
          >
            {{ copiedPublicUrl ? 'Copied!' : 'Copy public URL' }}
          </button>
        </div>

        <p class="mt-3 break-all text-text-muted">{{ publicFolderPath }}</p>
      </UiPanel>

      <UiPanel>
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="mb-1 text-[0.85rem] font-semibold uppercase tracking-[0.14em] text-primary">
              Upload Sign
            </p>
            <h2 class="text-[1.35rem] text-heading">Upload new signs</h2>
          </div>
          <p class="text-text-muted">PNG, JPEG, or WebP</p>
        </div>

        <UiErrorBanner v-if="signsStore.error">
          {{ signsStore.error }}
        </UiErrorBanner>

        <form class="mt-4 grid gap-4" @submit.prevent="handleUploadSubmit">
          <UiFormField label="File" name="file">
            <input
              ref="fileInput"
              type="file"
              name="file"
              multiple
              accept="image/png,image/jpeg,image/webp"
              required
              class="w-full rounded-xl border border-border bg-surface-input px-4 py-[0.85rem] text-heading file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-primary file:px-3 file:py-1 file:text-xs file:font-semibold file:text-sky-950"
              @change="handleFileChange"
            />
          </UiFormField>

          <div class="flex flex-wrap items-center gap-3">
            <UiButton variant="primary" type="submit" :disabled="signsStore.isUploading">
              {{ signsStore.isUploading ? 'Uploading...' : 'Upload sign' }}
            </UiButton>
            <p v-if="selectedFiles.length" class="text-text-muted">
              Selected: {{ selectedFiles.map((file) => file.name).join(', ') }}
            </p>
          </div>
        </form>
      </UiPanel>

      <UiPanel>
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="mb-1 text-[0.85rem] font-semibold uppercase tracking-[0.14em] text-primary">
              Signs
            </p>
            <h2 class="text-[1.35rem] text-heading">Folder signs</h2>
          </div>
          <p class="text-text-muted">{{ signsStore.signs.length }} total</p>
        </div>

        <p v-if="signsStore.isLoading" class="mt-4 text-text-muted">Loading signs...</p>
        <p v-else-if="signsStore.signs.length === 0" class="mt-4 text-text-muted">
          No signs uploaded yet.
        </p>

        <div v-else class="mt-4 grid gap-4">
          <UiSignCard
            v-for="sign in signsStore.signs"
            :key="sign.id"
            :id="sign.id"
            :name="sign.name"
            :public-url="sign.public_url"
            :mime-type="sign.mime_type"
            :width="sign.width"
            :height="sign.height"
            :size-bytes="sign.size_bytes"
            :copied="copiedSignId === sign.id"
            :show-delete="true"
            :show-size="true"
            @copy="handleCopy"
            @delete="handleDelete"
          />
        </div>
      </UiPanel>
    </div>
  </UiCard>
</template>
