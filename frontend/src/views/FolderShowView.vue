<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import { useFoldersStore } from '@/stores/folders'
import { useSignsStore } from '@/stores/signs'

import UiErrorBanner from '@/components/ui/UiErrorBanner.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import SignGrid from '@/components/signs/SignGrid.vue'
import UploadSignsModal from '@/components/signs/UploadSignsModal.vue'
import EditFolderModal from '@/components/folders/EditFolderModal.vue'

const foldersStore = useFoldersStore()
const signsStore = useSignsStore()
const route = useRoute()

const folderId = computed(() => Number(route.params.id))
const folder = computed(() => foldersStore.currentFolder)

const showUploadModal = ref(false)
const showEditModal = ref(false)
const copiedSignId = ref<number | null>(null)
const copiedPublicUrl = ref(false)

function visibilityLabel(visibility: string) {
  return visibility.charAt(0).toUpperCase() + visibility.slice(1)
}

const publicFolderPath = computed(() => `/public/folders/${folder.value?.slug ?? ''}`)

function canShareFolder() {
  return folder.value?.visibility !== 'private'
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

onUnmounted(() => {
  foldersStore.clearCurrentFolder()
  signsStore.clearCurrentSign()
  signsStore.signs = []
})

watch(folderId, () => {
  foldersStore.clearCurrentFolder()
  signsStore.clearCurrentSign()
  signsStore.signs = []
  copiedSignId.value = null
  copiedPublicUrl.value = false
  void loadFolder()
})

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
  signsStore.clearError()
  try {
    await navigator.clipboard.writeText(publicFolderPath.value)
    copiedPublicUrl.value = true
    window.setTimeout(() => {
      copiedPublicUrl.value = false
    }, 1500)
  } catch {
    signsStore.error = 'Could not copy the public URL. Please copy it manually.'
  }
}
</script>

<template>
  <div>
    <RouterLink
      to="/folders"
      class="text-sm flex items-center gap-x-2 text-orange-400 underline-offset-2 hover:text-orange-200"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="3"
        stroke="currentColor"
        class="size-4"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"
        />
      </svg>

      <span>Back to folders</span>
    </RouterLink>

    <div v-if="foldersStore.error && !folder" class="mt-3">
      <UiErrorBanner>{{ foldersStore.error }}</UiErrorBanner>
    </div>

    <p v-if="foldersStore.isLoading && !folder" class="mt-3 text-zinc-400">Loading folder...</p>

    <div v-else-if="folder" class="mt-3">
      <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <div class="flex items-center gap-4">
            <h1 class="text-[clamp(2rem,4vw,2.5rem)] leading-none text-white">
              {{ folder.name }}
            </h1>
            <UiBadge class="mt-1.5" :label="visibilityLabel(folder.visibility)" />
          </div>
          <p class="font-mono text-xs text-zinc-400 mt-2">{{ folder.slug }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <UiButton variant="secondary" type="button" @click="showEditModal = true">
            Edit
          </UiButton>

          <UiButton
            v-if="canShareFolder()"
            variant="secondary"
            type="button"
            @click="handleCopyPublicUrl"
          >
            {{ copiedPublicUrl ? 'Copied!' : 'Copy public URL' }}
          </UiButton>

          <UiButton variant="primary" type="button" @click="showUploadModal = true">
            Upload signs
          </UiButton>
        </div>
      </header>

      <UiErrorBanner v-if="signsStore.error && folder" class="mt-4">
        {{ signsStore.error }}
      </UiErrorBanner>

      <div class="mt-6">
        <p v-if="signsStore.isLoading" class="text-zinc-400">Loading signs...</p>
        <p v-else-if="signsStore.signs.length === 0" class="text-zinc-400">
          No signs uploaded yet.
        </p>
        <SignGrid
          v-else
          :signs="signsStore.signs"
          :copied-sign-id="copiedSignId"
          @copy="handleCopy"
        />
      </div>
    </div>

    <UploadSignsModal
      v-if="folder"
      v-model="showUploadModal"
      :folder-id="folder.id"
      @saved="signsStore.fetchFolderSigns(folder.id)"
    />

    <EditFolderModal
      v-if="folder"
      v-model="showEditModal"
      :folder-id="folder.id"
    />
  </div>
</template>
