<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import { useFoldersStore } from '@/stores/folders'
import { useSignsStore } from '@/stores/signs'

import UiErrorBanner from '@/components/ui/UiErrorBanner.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiModal from '@/components/ui/UiModal.vue'
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
const showDeleteConfirm = ref(false)
const copiedSignId = ref<number | null>(null)
const copiedPublicUrl = ref(false)
const selectedSignIds = ref<number[]>([])
const isDeleting = ref(false)

function visibilityLabel(visibility: string) {
  return visibility.charAt(0).toUpperCase() + visibility.slice(1)
}

const publicFolderPath = computed(() => {
  const origin = window.location.origin
  const path = `/public/folders/${folder.value?.slug ?? ''}`
  return `${origin}${path}`
})

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
  selectedSignIds.value = []
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

async function handleDeleteSelected() {
  isDeleting.value = true
  try {
    await signsStore.deleteSigns(selectedSignIds.value)
    selectedSignIds.value = []
    showDeleteConfirm.value = false
  } catch {
    // error is set in the store
  } finally {
    isDeleting.value = false
  }
}

function clearSelection() {
  selectedSignIds.value = []
}
</script>

<template>
  <div>
    <RouterLink
      to="/folders"
      class="text-sm flex items-center gap-x-2 text-emerald-400 underline-offset-2 hover:text-emerald-200"
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
          class="mb-13"
          :signs="signsStore.signs"
          :copied-sign-id="copiedSignId"
          v-model="selectedSignIds"
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

    <EditFolderModal v-if="folder" v-model="showEditModal" :folder-id="folder.id" />

    <Transition name="toolbar">
      <div v-if="selectedSignIds.length > 0" class="fixed flex flex-col bottom-0 top-0 left-2 z-40">
        <div
          class="bg-black/60 backdrop-blur border border-white/20 shadow-2xl px-4 py-3 rounded-2xl my-auto flex flex-col max-w-3xl items-center justify-between"
        >
          <p class="text-sm text-zinc-300 mb-6">
            <span class="font-semibold text-white">{{ selectedSignIds.length }}</span>
            selected
          </p>

          <div class="flex flex-col items-center gap-3">
            <UiButton class="w-full" variant="secondary" type="button" @click="clearSelection">
              Clear
            </UiButton>

            <UiButton
              class="w-full"
              variant="primary"
              type="button"
              @click="showDeleteConfirm = true"
            >
              Move
            </UiButton>

            <UiButton
              class="w-full"
              variant="danger"
              type="button"
              @click="showDeleteConfirm = true"
            >
              Delete
            </UiButton>
          </div>
        </div>
      </div>
    </Transition>

    <UiModal v-model="showDeleteConfirm" title="Delete signs">
      <p class="text-zinc-300 text-sm">
        Are you sure you want to delete
        <span class="font-semibold text-white">{{ selectedSignIds.length }}</span>
        sign{{ selectedSignIds.length === 1 ? '' : 's' }}? This action cannot be undone.
      </p>

      <div class="mt-6 flex justify-end gap-3">
        <UiButton variant="secondary" type="button" @click="showDeleteConfirm = false">
          Cancel
        </UiButton>

        <UiButton
          variant="danger"
          type="button"
          :disabled="isDeleting"
          @click="handleDeleteSelected"
        >
          {{ isDeleting ? 'Deleting...' : 'Delete' }}
        </UiButton>
      </div>
    </UiModal>
  </div>
</template>

<style scoped>
.toolbar-enter-active {
  transition: transform 0.25s ease-out;
}

.toolbar-leave-active {
  transition: transform 0.2s ease-in;
}

.toolbar-enter-from,
.toolbar-leave-to {
  transform: translateX(-100%);
}
</style>
