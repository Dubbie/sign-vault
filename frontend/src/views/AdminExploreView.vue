<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getAllFolders, getAdminFolderSigns, banUser, deleteAdminFolder, deleteAdminSign } from '@/lib/admin'
import type { PaginationMeta, PublicFolderListing } from '@/types/public-folder'
import { useAuthStore } from '@/stores/auth'

import UiInput from '@/components/ui/UiInput.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiErrorBanner from '@/components/ui/UiErrorBanner.vue'
import UiFormField from '@/components/ui/UiFormField.vue'
import UiModal from '@/components/ui/UiModal.vue'
import AdminSignGrid from '@/components/admin/AdminSignGrid.vue'
import type { AdminGridSign } from '@/components/admin/AdminSignGrid.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const folders = ref<PublicFolderListing[]>([])
const isLoading = ref(true)
const error = ref<string | null>(null)
const search = ref(String(route.query.q || ''))
const meta = ref<PaginationMeta | null>(null)
const selectedFolder = ref<PublicFolderListing | null>(null)
const selectedFolderUserId = ref<number | null>(null)
const selectedFolderSigns = ref<AdminGridSign[]>([])
const selectedFolderName = ref('')
const isLoadingSigns = ref(false)

// Sign selection
const selectedSignIds = ref<number[]>([])
const showDeleteSignsConfirm = ref(false)
const isDeletingSign = ref(false)

// Ban modal
const showBanModal = ref(false)
const banReason = ref('')
const isBanning = ref(false)

// Delete folder modal
const showDeleteFolderModal = ref(false)
const isDeletingFolder = ref(false)

function canBan() {
  return (
    authStore.isAdmin &&
    selectedFolderUserId.value !== null &&
    selectedFolderUserId.value !== authStore.user?.id
  )
}

async function loadFolders(page = 1) {
  isLoading.value = true
  error.value = null
  selectedFolder.value = null
  selectedFolderUserId.value = null
  selectedFolderSigns.value = []
  selectedFolderName.value = ''
  selectedSignIds.value = []

  try {
    const params: { q?: string; page?: number } = { page }
    if (search.value) params.q = search.value

    const response = await getAllFolders(params)
    folders.value = response.data
    meta.value = response.meta
  } catch {
    error.value = 'Failed to load folders.'
  } finally {
    isLoading.value = false
  }
}

async function loadFolderSigns(folder: PublicFolderListing) {
  selectedFolder.value = folder
  isLoadingSigns.value = true
  error.value = null
  selectedSignIds.value = []

  try {
    const result = await getAdminFolderSigns(folder.id)
    selectedFolderSigns.value = result.signs
    selectedFolderName.value = result.folder.name
    selectedFolderUserId.value = result.folder.user_id
  } catch {
    error.value = 'Failed to load folder signs.'
  } finally {
    isLoadingSigns.value = false
  }
}

async function handleBan() {
  if (selectedFolderUserId.value === null || !banReason.value.trim()) return

  isBanning.value = true
  error.value = null

  try {
    await banUser(selectedFolderUserId.value, banReason.value.trim())
    showBanModal.value = false
    banReason.value = ''
    selectedFolder.value = null
    selectedFolderUserId.value = null
    selectedFolderSigns.value = []
    selectedFolderName.value = ''
    selectedSignIds.value = []
    await loadFolders(meta.value?.current_page ?? 1)
  } catch {
    error.value = 'Failed to ban user.'
  } finally {
    isBanning.value = false
  }
}

async function handleDeleteFolder() {
  if (!selectedFolder.value) return

  isDeletingFolder.value = true
  error.value = null
  const folderId = selectedFolder.value.id

  try {
    await deleteAdminFolder(folderId)
    showDeleteFolderModal.value = false
    folders.value = folders.value.filter((f) => f.id !== folderId)
    selectedFolder.value = null
    selectedFolderUserId.value = null
    selectedFolderSigns.value = []
    selectedFolderName.value = ''
    selectedSignIds.value = []
  } catch {
    error.value = 'Failed to delete folder.'
  } finally {
    isDeletingFolder.value = false
  }
}

async function handleDeleteSelectedSigns() {
  if (selectedSignIds.value.length === 0) return

  isDeletingSign.value = true
  error.value = null
  const idsToDelete = [...selectedSignIds.value]

  try {
    await Promise.all(idsToDelete.map((id) => deleteAdminSign(id)))
    selectedFolderSigns.value = selectedFolderSigns.value.filter((s) => !idsToDelete.includes(s.id))
    selectedSignIds.value = []
    showDeleteSignsConfirm.value = false
    if (selectedFolder.value) {
      const newCount = Math.max(0, selectedFolder.value.signs_count - idsToDelete.length)
      selectedFolder.value = { ...selectedFolder.value, signs_count: newCount }
      const folderInList = folders.value.find((f) => f.id === selectedFolder.value!.id)
      if (folderInList) folderInList.signs_count = newCount
    }
  } catch {
    error.value = 'Failed to delete signs.'
  } finally {
    isDeletingSign.value = false
  }
}

function goToPage(page: number) {
  if (!meta.value) return
  const query: Record<string, string> = {}
  if (search.value) query.q = search.value
  if (page > 1) query.page = String(page)
  router.replace({ query }).catch(() => {})
  void loadFolders(page)
}

let searchTimeout: ReturnType<typeof setTimeout> | null = null
function handleSearchInput(value: string) {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    search.value = value
    goToPage(1)
  }, 400)
}

const pages = ref<number[]>([])
watch(meta, (m) => {
  if (!m) return
  const p: number[] = []
  for (let i = 1; i <= m.last_page; i++) p.push(i)
  pages.value = p
})

const page = ref(1)
watch(
  () => route.query,
  (query) => {
    page.value = Number(query.page) || 1
    if (typeof query.q === 'string') search.value = query.q
  },
  { immediate: true },
)

onMounted(() => {
  void loadFolders(page.value)
})
</script>

<template>
  <div class="mx-auto max-w-7xl">
    <div class="flex items-end justify-between gap-4">
      <h1 class="text-headline-xl text-on-surface">All Folders</h1>
    </div>

    <div class="mt-6">
      <UiInput
        :model-value="search"
        placeholder="Search folders by name..."
        @update:model-value="handleSearchInput"
      />
    </div>

    <UiErrorBanner v-if="error">
      {{ error }}
    </UiErrorBanner>

    <p v-if="isLoading" class="mt-8 text-zinc-400">Loading folders...</p>

    <p v-else-if="!isLoading && folders.length === 0" class="mt-8 text-zinc-400">
      {{ search ? 'No folders match your search.' : 'No folders found.' }}
    </p>

    <div v-else class="mt-6">
      <!-- lg+: two-column layout -->
      <div class="hidden lg:grid gap-6" style="grid-template-columns: 350px 1fr">
        <div class="flex flex-col gap-2">
          <div
            v-for="folder in folders"
            :key="folder.id"
            class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-white/20 bg-surface p-3 no-underline transition hover:border-emerald-400/50 hover:bg-surface-hover/50"
            :class="{ 'ring-1 ring-emerald-400 border-emerald-400/50': selectedFolder === folder }"
            @click="loadFolderSigns(folder)"
          >
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-semibold text-zinc-100">{{ folder.name }}</p>
              <div v-if="folder.owner" class="mt-1 flex items-center gap-1.5">
                <img
                  v-if="folder.owner.discord_avatar"
                  :src="folder.owner.discord_avatar"
                  :alt="folder.owner.discord_username"
                  class="size-4 rounded-full"
                />
                <span class="truncate text-xs text-zinc-500">
                  {{ folder.owner.discord_global_name || folder.owner.discord_username }}
                </span>
              </div>
            </div>
            <span
              class="shrink-0 rounded-full bg-emerald-400/10 px-2 py-0.5 text-xs font-semibold text-emerald-400"
            >
              {{ folder.signs_count }}
            </span>
          </div>
        </div>

        <div>
          <div
            v-if="!selectedFolder"
            class="flex min-h-[288px] items-center justify-center rounded-xl border border-dashed border-white/10"
          >
            <p class="text-sm text-zinc-500">Click a folder to view its signs</p>
          </div>

          <div v-else-if="isLoadingSigns">
            <p class="mt-8 text-zinc-400">Loading signs...</p>
          </div>

          <div v-else>
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0 flex-1">
                <h2 class="text-lg font-semibold text-zinc-100">{{ selectedFolderName || selectedFolder.name }}</h2>
                <div v-if="selectedFolder.owner" class="mt-1 flex items-center gap-2">
                  <img
                    v-if="selectedFolder.owner.discord_avatar"
                    :src="selectedFolder.owner.discord_avatar"
                    :alt="selectedFolder.owner.discord_username"
                    class="size-5 rounded-full"
                  />
                  <span class="truncate text-sm text-zinc-400">
                    {{
                      selectedFolder.owner.discord_global_name ||
                      selectedFolder.owner.discord_username
                    }}
                  </span>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <span
                  class="shrink-0 rounded-full bg-emerald-400/10 px-2.5 py-1 text-xs font-semibold text-emerald-400"
                >
                  {{ selectedFolder.signs_count }} signs
                </span>
                <UiButton variant="danger" type="button" @click="showDeleteFolderModal = true">
                  Delete Folder
                </UiButton>
                <UiButton v-if="canBan()" variant="danger" type="button" @click="showBanModal = true">
                  Ban User
                </UiButton>
              </div>
            </div>

            <div v-if="selectedFolderSigns.length > 0" class="mt-4">
              <AdminSignGrid
                v-model="selectedSignIds"
                :signs="selectedFolderSigns"
              />
            </div>
            <p v-else class="mt-4 text-sm text-zinc-500">No signs in this folder.</p>
          </div>
        </div>
      </div>

      <!-- <lg: mobile fallback grid -->
      <div class="grid gap-4 sm:grid-cols-2 lg:hidden">
        <div
          v-for="folder in folders"
          :key="folder.id"
          class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-white/20 bg-surface p-3 no-underline transition hover:border-emerald-400/50 hover:bg-surface-hover/50"
          @click="loadFolderSigns(folder)"
        >
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-zinc-100">{{ folder.name }}</p>
            <div v-if="folder.owner" class="mt-1 flex items-center gap-1.5">
              <img
                v-if="folder.owner.discord_avatar"
                :src="folder.owner.discord_avatar"
                :alt="folder.owner.discord_username"
                class="size-4 rounded-full"
              />
              <span class="truncate text-xs text-zinc-500">
                {{ folder.owner.discord_global_name || folder.owner.discord_username }}
              </span>
            </div>
          </div>
          <span
            class="shrink-0 rounded-full bg-emerald-400/10 px-2 py-0.5 text-xs font-semibold text-emerald-400"
          >
            {{ folder.signs_count }}
          </span>
        </div>
      </div>
    </div>

    <nav v-if="meta && meta.last_page > 1" class="mt-10 flex items-center justify-center gap-2">
      <UiButton
        variant="secondary"
        :disabled="meta.current_page <= 1"
        @click="goToPage(meta.current_page - 1)"
      >
        Previous
      </UiButton>

      <button
        v-for="p in pages"
        :key="p"
        type="button"
        class="flex size-9 items-center justify-center rounded text-sm font-semibold transition"
        :class="
          p === meta.current_page
            ? 'bg-emerald-400 text-background'
            : 'text-zinc-400 hover:text-zinc-100'
        "
        @click="goToPage(p)"
      >
        {{ p }}
      </button>

      <UiButton
        variant="secondary"
        :disabled="meta.current_page >= meta.last_page"
        @click="goToPage(meta.current_page + 1)"
      >
        Next
      </UiButton>
    </nav>

    <!-- Selection toolbar -->
    <Transition name="toolbar">
      <div v-if="selectedSignIds.length > 0" class="fixed flex flex-col bottom-0 top-0 right-2 z-40">
        <div
          class="bg-background/60 backdrop-blur border border-outline-variant/30 shadow-2xl p-3 rounded-xl my-auto flex flex-col items-center"
        >
          <p class="text-sm text-zinc-300 mb-6">
            <span class="font-semibold text-on-surface">{{ selectedSignIds.length }}</span>
            selected
          </p>

          <div class="flex flex-col items-center gap-3">
            <UiButton class="w-full" variant="secondary" type="button" @click="selectedSignIds = []">
              Clear
            </UiButton>
            <UiButton class="w-full" variant="danger" type="button" @click="showDeleteSignsConfirm = true">
              Delete
            </UiButton>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Ban modal -->
    <UiModal
      :model-value="showBanModal"
      title="Ban User"
      @update:model-value="showBanModal = false"
    >
      <div v-if="selectedFolder">
        <p class="text-sm text-zinc-300">
          Ban <strong>{{ selectedFolder.owner?.discord_global_name || selectedFolder.owner?.discord_username }}</strong>? This will:
        </p>
        <ul class="mt-2 list-inside list-disc text-sm text-zinc-400">
          <li>Delete all their folders and signs</li>
          <li>Revoke all active sessions</li>
          <li>Prevent them from logging in</li>
        </ul>

        <UiFormField label="Ban reason" name="reason" class="mt-4">
          <UiInput
            v-model="banReason"
            placeholder="Why is this user being banned?"
            maxlength="500"
            required
          />
        </UiFormField>

        <div class="mt-4 flex justify-end gap-3">
          <UiButton variant="secondary" @click="showBanModal = false">Cancel</UiButton>
          <UiButton
            variant="danger"
            :disabled="!banReason.trim() || isBanning"
            @click="handleBan"
          >
            {{ isBanning ? 'Banning...' : 'Ban & Nuke Content' }}
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Delete folder modal -->
    <UiModal
      :model-value="showDeleteFolderModal"
      title="Delete Folder"
      @update:model-value="showDeleteFolderModal = false"
    >
      <div v-if="selectedFolder">
        <p class="text-sm text-zinc-300">
          Permanently delete <strong>{{ selectedFolderName || selectedFolder.name }}</strong>?
          This removes all {{ selectedFolder.signs_count }} sign(s) and cannot be undone.
          The user account will not be affected.
        </p>

        <div class="mt-6 flex justify-end gap-3">
          <UiButton variant="secondary" @click="showDeleteFolderModal = false">Cancel</UiButton>
          <UiButton
            variant="danger"
            :disabled="isDeletingFolder"
            @click="handleDeleteFolder"
          >
            {{ isDeletingFolder ? 'Deleting...' : 'Delete Folder' }}
          </UiButton>
        </div>
      </div>
    </UiModal>

    <!-- Delete signs modal -->
    <UiModal
      :model-value="showDeleteSignsConfirm"
      title="Delete Signs"
      @update:model-value="showDeleteSignsConfirm = false"
    >
      <p class="text-sm text-zinc-300">
        Permanently delete
        <strong>{{ selectedSignIds.length }} sign{{ selectedSignIds.length === 1 ? '' : 's' }}</strong>?
        This cannot be undone.
      </p>

      <div class="mt-6 flex justify-end gap-3">
        <UiButton variant="secondary" @click="showDeleteSignsConfirm = false">Cancel</UiButton>
        <UiButton
          variant="danger"
          :disabled="isDeletingSign"
          @click="handleDeleteSelectedSigns"
        >
          {{ isDeletingSign ? 'Deleting...' : 'Delete' }}
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
  transform: translateX(100%);
}
</style>
