<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { Globe, Plus, Shield, Shapes, HatGlasses } from '@lucide/vue'

import { useFoldersStore } from '@/stores/folders'

import UiButton from '@/components/ui/UiButton.vue'
import UiErrorBanner from '@/components/ui/UiErrorBanner.vue'
import EditFolderModal from '@/components/folders/EditFolderModal.vue'
import CreateFolderModal from '@/components/folders/CreateFolderModal.vue'
import DeleteFolderModal from '@/components/folders/DeleteFolderModal.vue'
import StatCard from '@/components/ui/StatCard.vue'

const foldersStore = useFoldersStore()
const router = useRouter()

const isFetchingFolders = ref(true)

const editingFolderId = ref<number | null>(null)
const showEditModal = computed({
  get: () => editingFolderId.value !== null,
  set: (v) => {
    if (!v) editingFolderId.value = null
  },
})

const deletingFolderId = ref<number | null>(null)
const showDeleteModal = computed({
  get: () => deletingFolderId.value !== null,
  set: (v) => {
    if (!v) deletingFolderId.value = null
  },
})

const showCreateModal = ref(false)

const dateFormatter = new Intl.DateTimeFormat(undefined, {
  dateStyle: 'medium',
  timeStyle: 'short',
})

const publicFolderCount = computed(
  () => foldersStore.folders.filter((folder) => folder.visibility === 'public').length,
)
const privateFolderCount = computed(
  () => foldersStore.folders.filter((folder) => folder.visibility === 'private').length,
)
const protectedFolderCount = computed(
  () => foldersStore.folders.filter((folder) => folder.visibility === 'password').length,
)
const totalVariantCount = computed(() =>
  foldersStore.folders.reduce((count, folder) => count + folder.variants.length, 0),
)

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

function visibilityTone(visibility: string) {
  if (visibility === 'public') {
    return 'bg-primary/10 text-primary border-primary/20'
  }

  if (visibility === 'password') {
    return 'bg-secondary/10 text-secondary border-secondary/20'
  }

  return 'bg-outline-variant/30 text-on-surface-variant border-outline-variant/60'
}

function handleCreateSaved(folderId: number) {
  router.push({ name: 'folders-show', params: { id: folderId } })
}
</script>

<template>
  <div class="mx-auto max-w-7xl space-y-gutter">
    <div class="flex items-end justify-between gap-4">
      <div>
        <h1 class="text-headline-xl text-on-surface">My folders</h1>
      </div>

      <UiButton type="button" @click="showCreateModal = true">
        <Plus class="size-5" />
        Create folder
      </UiButton>
    </div>

    <UiErrorBanner v-if="foldersStore.error">
      {{ foldersStore.error }}
    </UiErrorBanner>

    <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
      <StatCard
        :icon="HatGlasses"
        label="Private folders"
        :value="privateFolderCount.toLocaleString()"
      />
      <StatCard :icon="Globe" label="Public folders" :value="publicFolderCount.toLocaleString()" />
      <StatCard
        :icon="Shield"
        label="Password folders"
        :value="protectedFolderCount.toLocaleString()"
      />
      <StatCard :icon="Shapes" label="Total variants" :value="totalVariantCount.toLocaleString()" />
    </div>

    <p v-if="isFetchingFolders" class="text-on-surface-variant">Loading folders...</p>

    <div
      v-else-if="foldersStore.folders.length === 0"
      class="rounded-2xl border border-dashed border-outline-variant p-10 text-center space-y-4"
    >
      <p class="text-headline-md text-on-surface">No folders yet</p>
      <p class="mx-auto max-w-xl text-body-md text-on-surface-variant">
        Start a new folder to upload signs, create variants, and publish a curated public view when
        you are ready.
      </p>
      <div class="flex justify-center">
        <UiButton variant="secondary" type="button" @click="showCreateModal = true">
          <Plus class="size-5" />
          Create your first folder
        </UiButton>
      </div>
    </div>

    <div v-else class="space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-headline-md text-on-surface">Folder library</h2>
        <span class="text-label-md text-on-surface-variant">
          {{ foldersStore.folderCount }} total
        </span>
      </div>

      <div class="glass-card overflow-hidden rounded-lg">
        <div class="overflow-x-auto">
          <table class="min-w-full">
            <thead class="bg-surface-container-low">
              <tr class="text-left">
                <th class="px-5 py-4 text-label-sm text-on-surface-variant">Folder</th>
                <th class="px-5 py-4 text-label-sm text-on-surface-variant max-md:hidden">Slug</th>
                <th class="px-5 py-4 text-label-sm text-on-surface-variant">Visibility</th>
                <th
                  class="px-5 py-4 text-label-sm text-on-surface-variant text-right max-sm:hidden"
                >
                  Variants
                </th>
                <th
                  class="px-5 py-4 text-label-sm text-on-surface-variant text-center max-lg:hidden"
                >
                  Created
                </th>
                <th class="px-5 py-4 text-label-sm text-on-surface-variant text-right">Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="folder in foldersStore.folders"
                :key="folder.id"
                class="group border-t border-outline-variant/60 transition hover:bg-surface-container-low"
              >
                <td class="px-5 py-4">
                  <div class="min-w-0">
                    <RouterLink
                      :to="{ name: 'folders-show', params: { id: folder.id } }"
                      class="block truncate text-body-md font-semibold text-on-surface no-underline hover:text-primary"
                    >
                      {{ folder.name }}
                    </RouterLink>
                    <p class="mt-1 text-label-sm text-on-surface-variant sm:hidden">
                      {{ folder.slug }}
                    </p>
                  </div>
                </td>

                <td class="px-5 py-4 max-md:hidden">
                  <span class="font-mono text-sm text-on-surface-variant">{{ folder.slug }}</span>
                </td>

                <td class="px-5 py-4">
                  <span
                    class="inline-flex items-center rounded-full border px-2.5 py-1 text-label-sm"
                    :class="visibilityTone(folder.visibility)"
                  >
                    {{ visibilityLabel(folder.visibility) }}
                  </span>
                </td>

                <td class="px-5 py-4 text-right max-sm:hidden">
                  <span class="text-body-md text-on-surface">{{ folder.variants.length }}</span>
                </td>

                <td class="px-5 py-4 text-center max-lg:hidden">
                  <span class="text-sm text-on-surface-variant">{{
                    formatDate(folder.created_at)
                  }}</span>
                </td>

                <td class="px-5 py-4">
                  <div class="flex flex-wrap items-center justify-end gap-2">
                    <div
                      class="flex flex-wrap items-center gap-2 pointer-events-none opacity-0 transition-all -translate-x-10 group-hover:opacity-100 group-hover:pointer-events-auto group-hover:translate-x-0"
                    >
                      <UiButton
                        size="sm"
                        variant="danger"
                        type="button"
                        @click="deletingFolderId = folder.id"
                      >
                        Delete
                      </UiButton>

                      <UiButton
                        v-if="folder.visibility !== 'private'"
                        size="sm"
                        variant="tertiary"
                        :to="{ name: 'public-folder', params: { slug: folder.public_slug } }"
                      >
                        Preview
                      </UiButton>
                      <UiButton v-else size="sm" variant="tertiary" disabled> Preview </UiButton>

                      <UiButton
                        size="sm"
                        variant="secondary"
                        type="button"
                        @click="editingFolderId = folder.id"
                      >
                        Edit
                      </UiButton>
                    </div>
                    <UiButton
                      size="sm"
                      variant="primary"
                      :to="{ name: 'folders-show', params: { id: folder.id } }"
                    >
                      Open
                    </UiButton>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <CreateFolderModal v-model="showCreateModal" @saved="handleCreateSaved" />

    <EditFolderModal v-model="showEditModal" :folder-id="editingFolderId ?? 0" />

    <DeleteFolderModal v-model="showDeleteModal" :folder-id="deletingFolderId ?? 0" />
  </div>
</template>
