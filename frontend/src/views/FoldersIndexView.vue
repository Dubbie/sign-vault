<script setup lang="ts">
import { onMounted, ref } from 'vue'
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

const editingFolderId = ref<number | null>(null)
const showCreateModal = ref(false)
const deletingFolderId = ref<number | null>(null)

const dateFormatter = new Intl.DateTimeFormat(undefined, {
  dateStyle: 'medium',
  timeStyle: 'short',
})

onMounted(async () => {
  await foldersStore.fetchFolders()
})

function formatDate(value: string) {
  return dateFormatter.format(new Date(value))
}

function visibilityLabel(visibility: string) {
  return visibility.charAt(0).toUpperCase() + visibility.slice(1)
}

function handleEdit(folderId: number) {
  editingFolderId.value = folderId
}

function handleCloseEdit() {
  editingFolderId.value = null
}

function handleDelete(folderId: number) {
  deletingFolderId.value = folderId
}

function handleCreate() {
  showCreateModal.value = true
}

function handleCreateSaved() {
  const folder = foldersStore.currentFolder
  if (!folder) return
  router.push({ name: 'folders-show', params: { id: folder.id } })
}
</script>

<template>
  <div class="mx-auto max-w-7xl">
    <div class="flex items-start justify-between gap-4">
      <div>
        <h1 class="text-[clamp(2rem,4vw,2.5rem)] leading-tight text-white">My folders</h1>
      </div>

      <UiButton variant="primary" type="button" @click="handleCreate"> Create folder </UiButton>
    </div>

    <UiErrorBanner v-if="foldersStore.error">
      {{ foldersStore.error }}
    </UiErrorBanner>

    <p v-if="foldersStore.isLoading" class="mt-3 text-zinc-400">Loading folders...</p>

    <p v-else-if="foldersStore.folders.length === 0" class="mt-3 text-zinc-400">No folders yet.</p>

    <div v-else class="mt-3 overflow-x-auto">
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
            class="rounded-xl bg-black transition border-b border-white/20 last-of-type:border-0 hover:bg-zinc-900"
          >
            <td class="rounded-l-xl px-4 py-3">
              <RouterLink
                :to="{ name: 'folders-show', params: { id: folder.id } }"
                class="font-semibold text-white no-underline hover:underline"
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
                  class="rounded-lg border border-border px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-white/5"
                  @click="handleEdit(folder.id)"
                >
                  Edit
                </button>
                <button
                  type="button"
                  class="rounded-lg border border-border-danger px-3 py-1.5 text-xs font-semibold text-danger-text transition hover:bg-white/5"
                  @click="handleDelete(folder.id)"
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

    <EditFolderModal
      v-if="editingFolderId"
      :model-value="true"
      :folder-id="editingFolderId"
      @update:model-value="handleCloseEdit"
    />

    <DeleteFolderModal
      v-if="deletingFolderId"
      :model-value="true"
      :folder-id="deletingFolderId"
      @update:model-value="deletingFolderId = null"
    />
  </div>
</template>
