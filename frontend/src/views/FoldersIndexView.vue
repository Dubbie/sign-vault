<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import { useFoldersStore } from '@/stores/folders'

import UiButton from '@/components/ui/UiButton.vue'
import UiErrorBanner from '@/components/ui/UiErrorBanner.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import EditFolderModal from '@/components/folders/EditFolderModal.vue'
import CreateFolderModal from '@/components/folders/CreateFolderModal.vue'
import DeleteFolderModal from '@/components/folders/DeleteFolderModal.vue'

const foldersStore = useFoldersStore()
const router = useRouter()

const isFetchingFolders = ref(true)

const editingFolderId = ref<number | null>(null)
const showEditModal = computed({
  get: () => editingFolderId.value !== null,
  set: (v) => { if (!v) editingFolderId.value = null },
})

const deletingFolderId = ref<number | null>(null)
const showDeleteModal = computed({
  get: () => deletingFolderId.value !== null,
  set: (v) => { if (!v) deletingFolderId.value = null },
})

const showCreateModal = ref(false)

const dateFormatter = new Intl.DateTimeFormat(undefined, {
  dateStyle: 'medium',
  timeStyle: 'short',
})

onMounted(async () => {
  try {
    await foldersStore.fetchFolders()
  } finally {
    isFetchingFolders.value = false
  }
})

function formatDate(value: string) {
  return dateFormatter.format(new Date(value))
}

function visibilityLabel(visibility: string) {
  return visibility.charAt(0).toUpperCase() + visibility.slice(1)
}

function handleCreateSaved(folderId: number) {
  router.push({ name: 'folders-show', params: { id: folderId } })
}
</script>

<template>
  <div class="mx-auto max-w-7xl">
    <div class="flex items-start justify-between gap-4 mb-8">
      <div>
        <h1 class="text-[clamp(2rem,4vw,2.5rem)] leading-tight text-zinc-100">My folders</h1>
        <p class="mt-1 text-sm text-zinc-400">
          Create and manage your folders where you can upload signs.
        </p>
      </div>

      <UiButton variant="primary" type="button" @click="showCreateModal = true"> Create folder </UiButton>
    </div>

    <UiErrorBanner v-if="foldersStore.error">
      {{ foldersStore.error }}
    </UiErrorBanner>

    <p v-if="isFetchingFolders" class="mt-3 text-zinc-400">Loading folders...</p>

    <p v-else-if="foldersStore.folders.length === 0" class="mt-3 text-zinc-400">No folders yet.</p>

    <div v-else class="overflow-x-auto">
      <table class="w-full">
        <thead>
          <tr class="text-left text-xs font-semibold text-zinc-400">
            <th class="px-4 py-2">Name</th>
            <th class="px-4 py-2 max-sm:hidden">Slug</th>
            <th class="px-4 py-2">Visibility</th>
            <th class="px-4 py-2 max-md:hidden">Created</th>
            <th class="px-4 py-2 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="folder in foldersStore.folders"
            :key="folder.id"
            class="rounded-xl bg-background transition border-b border-white/20 last-of-type:border-0 hover:bg-surface"
          >
            <td class="rounded-l-xl px-4 py-3">
              <RouterLink
                :to="{ name: 'folders-show', params: { id: folder.id } }"
                class="font-semibold text-zinc-100 no-underline hover:underline"
              >
                {{ folder.name }}
              </RouterLink>
            </td>
            <td class="px-4 py-3 max-sm:hidden">
              <span class="font-mono text-xs text-zinc-400">{{ folder.slug }}</span>
            </td>
            <td class="px-4 py-3">
              <UiBadge :label="visibilityLabel(folder.visibility)" />
            </td>
            <td class="px-4 py-3 max-md:hidden">
              <span class="text-sm text-zinc-400">{{ formatDate(folder.created_at) }}</span>
            </td>
            <td class="rounded-r-xl px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-2">
                <button
                  type="button"
                  class="rounded border border-border px-3 py-1.5 text-xs font-semibold text-zinc-100 transition hover:bg-white/5"
                  @click="editingFolderId = folder.id"
                >
                  Edit
                </button>
                <button
                  type="button"
                  class="rounded border border-border-danger px-3 py-1.5 text-xs font-semibold text-danger-text transition hover:bg-white/5"
                  @click="deletingFolderId = folder.id"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <CreateFolderModal v-model="showCreateModal" @saved="handleCreateSaved" />

    <EditFolderModal v-model="showEditModal" :folder-id="editingFolderId ?? 0" />

    <DeleteFolderModal v-model="showDeleteModal" :folder-id="deletingFolderId ?? 0" />
  </div>
</template>
