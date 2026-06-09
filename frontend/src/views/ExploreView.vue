<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { getPublicFolders } from '@/lib/public-folders'
import type { PaginationMeta, PublicFolderListing } from '@/types/public-folder'

type SortOption = 'latest' | 'votes'

import UiInput from '@/components/ui/UiInput.vue'
import UiAvatar from '@/components/ui/UiAvatar.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiErrorBanner from '@/components/ui/UiErrorBanner.vue'
import ExploreFolderCard from '@/components/explore/ExploreFolderCard.vue'
import PreviewSignGrid from '@/components/explore/PreviewSignGrid.vue'
import { ChevronLeft, ChevronRight } from '@lucide/vue'

const route = useRoute()
const router = useRouter()

const folders = ref<PublicFolderListing[]>([])
const isLoading = ref(true)
const error = ref<string | null>(null)
const search = ref(String(route.query.q || ''))
const SORT_STORAGE_KEY = 'explore:sort'
const storedSort = localStorage.getItem(SORT_STORAGE_KEY) as SortOption | null
const sort = ref<SortOption>((route.query.sort as SortOption) || storedSort || 'votes')
const meta = ref<PaginationMeta | null>(null)
const hoveredFolder = ref<PublicFolderListing | null>(null)
const activeFolderIndex = ref(0)
const activeFolderId = computed(() => {
  const folder = folders.value[activeFolderIndex.value]
  return folder ? `explore-folder-${folder.id}` : undefined
})

function setActiveFolder(index: number) {
  if (folders.value.length === 0) {
    activeFolderIndex.value = 0
    hoveredFolder.value = null
    return
  }

  const nextIndex = Math.min(Math.max(index, 0), folders.value.length - 1)
  activeFolderIndex.value = nextIndex
  hoveredFolder.value = folders.value[nextIndex] ?? null
}

async function focusActiveFolderCard() {
  await nextTick()

  if (!activeFolderId.value) return

  const activeElement = document.getElementById(activeFolderId.value)
  if (activeElement instanceof HTMLElement) {
    activeElement.focus()
  }
}

function handleFolderListKeydown(event: KeyboardEvent) {
  if (folders.value.length === 0) return

  switch (event.key) {
    case 'ArrowDown':
    case 'ArrowRight':
      event.preventDefault()
      setActiveFolder(activeFolderIndex.value + 1)
      void focusActiveFolderCard()
      break
    case 'ArrowUp':
    case 'ArrowLeft':
      event.preventDefault()
      setActiveFolder(activeFolderIndex.value - 1)
      void focusActiveFolderCard()
      break
    case 'Home':
      event.preventDefault()
      setActiveFolder(0)
      void focusActiveFolderCard()
      break
    case 'End':
      event.preventDefault()
      setActiveFolder(folders.value.length - 1)
      void focusActiveFolderCard()
      break
  }
}

async function loadFolders(page = 1) {
  isLoading.value = true
  error.value = null

  try {
    const params: { q?: string; page?: number; sort?: SortOption } = { page }
    if (search.value) params.q = search.value
    if (sort.value !== 'votes') params.sort = sort.value

    const response = await getPublicFolders(params)
    folders.value = response.data
    meta.value = response.meta
    setActiveFolder(0)
  } catch {
    error.value = 'Failed to load public folders.'
  } finally {
    isLoading.value = false
  }
}

function goToPage(page: number) {
  if (!meta.value) return
  const query: Record<string, string> = {}
  if (search.value) query.q = search.value
  if (sort.value !== 'votes') query.sort = sort.value
  if (page > 1) query.page = String(page)
  router.replace({ query }).catch(() => {})
  void loadFolders(page)
}

function handleSortChange(value: SortOption) {
  sort.value = value
  localStorage.setItem(SORT_STORAGE_KEY, value)
  goToPage(1)
}

let searchTimeout: ReturnType<typeof setTimeout> | null = null
function handleSearchInput(value: string) {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    search.value = value
    goToPage(1)
  }, 400)
}

const MAX_VISIBLE_PAGES = 5
const visiblePages = computed<number[]>(() => {
  if (!meta.value) return []

  const { current_page, last_page } = meta.value
  let start = Math.max(1, current_page - Math.floor(MAX_VISIBLE_PAGES / 2))
  const end = Math.min(last_page, start + MAX_VISIBLE_PAGES - 1)
  start = Math.max(1, end - MAX_VISIBLE_PAGES + 1)

  const result: number[] = []
  for (let i = start; i <= end; i++) result.push(i)
  return result
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

watch(folders, (nextFolders) => {
  if (nextFolders.length === 0) {
    hoveredFolder.value = null
    activeFolderIndex.value = 0
    return
  }

  const currentId = hoveredFolder.value?.id
  if (currentId === undefined) {
    setActiveFolder(0)
    return
  }

  const nextIndex = nextFolders.findIndex((folder) => folder.id === currentId)
  setActiveFolder(nextIndex >= 0 ? nextIndex : 0)
})
</script>

<template>
  <div class="mx-auto max-w-7xl">
    <h1 class="text-headline-xl text-on-surface">Explore</h1>

    <div class="mt-6 flex gap-3">
      <UiInput
        class="flex-1"
        :model-value="search"
        placeholder="Search folders by name..."
        @update:model-value="handleSearchInput"
      />
      <div
        class="flex rounded-lg border border-outline/30 overflow-hidden text-sm font-medium shrink-0"
      >
        <button
          type="button"
          class="cursor-pointer px-3 py-2 transition-colors"
          :class="
            sort === 'votes'
              ? 'bg-primary/10 text-primary'
              : 'text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface'
          "
          @click="handleSortChange('votes')"
        >
          Top
        </button>
        <button
          type="button"
          class="cursor-pointer px-3 py-2 transition-colors border-l border-outline/30"
          :class="
            sort === 'latest'
              ? 'bg-primary/10 text-primary'
              : 'text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface'
          "
          @click="handleSortChange('latest')"
        >
          Latest
        </button>
      </div>
    </div>

    <UiErrorBanner v-if="error">
      {{ error }}
    </UiErrorBanner>

    <p v-if="isLoading" class="mt-8 text-on-surface">Loading folders...</p>

    <p v-else-if="!isLoading && folders.length === 0" class="mt-8 text-on-surface-variant">
      {{ search ? 'No folders match your search.' : 'No public folders yet.' }}
    </p>

    <div v-else class="mt-6">
      <!-- lg+: two-column layout -->
      <div class="hidden lg:grid gap-6 items-start" style="grid-template-columns: 350px 1fr">
        <div class="flex flex-col gap-6">
          <div
            role="listbox"
            aria-label="Public folders"
            :aria-activedescendant="activeFolderId"
            class="flex flex-col gap-2 rounded-xl"
            @keydown="handleFolderListKeydown"
          >
            <ExploreFolderCard
              v-for="(folder, index) in folders"
              :key="folder.id"
              :id="`explore-folder-${folder.id}`"
              :folder="folder"
              :active="hoveredFolder === folder"
              :tabindex="hoveredFolder === folder ? 0 : -1"
              @mouseenter="setActiveFolder(index)"
              @focus="setActiveFolder(index)"
            />
          </div>

          <nav
            v-if="meta && meta.last_page > 1"
            class="flex flex-wrap items-center justify-between gap-2"
          >
            <UiButton
              variant="secondary"
              class="size-10! p-0!"
              :disabled="meta.current_page <= 1"
              @click="goToPage(meta.current_page - 1)"
            >
              <ChevronLeft class="size-5" />
            </UiButton>

            <div class="flex flex-wrap items-center justify-between gap-2">
              <button
                v-for="p in visiblePages"
                :key="p"
                type="button"
                class="cursor-pointer flex size-9 items-center justify-center rounded-lg text-sm font-semibold transition"
                :class="
                  p === meta.current_page
                    ? 'bg-primary text-background'
                    : 'text-on-surface-variant hover:text-primary'
                "
                @click="goToPage(p)"
              >
                {{ p }}
              </button>
            </div>

            <UiButton
              variant="secondary"
              class="size-10! p-0!"
              :disabled="meta.current_page >= meta.last_page"
              @click="goToPage(meta.current_page + 1)"
            >
              <ChevronRight class="size-5" />
            </UiButton>
          </nav>
        </div>

        <div class="sticky top-20">
          <Transition name="preview-panel" mode="out-in">
            <div
              v-if="!hoveredFolder"
              key="empty"
              class="flex min-h-72 items-center justify-center rounded-xl border border-dashed border-white/10"
            >
              <p class="text-sm text-on-surface-variant">
                Hover over a folder to preview its signs
              </p>
            </div>

            <div v-else :key="hoveredFolder.id" class="min-h-72">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                  <h2 class="text-lg font-semibold text-on-surface">{{ hoveredFolder.name }}</h2>
                  <div v-if="hoveredFolder.owner" class="mt-1 flex items-center gap-2">
                    <UiAvatar
                      :name="hoveredFolder.owner.display_name"
                      :src="hoveredFolder.owner.avatar_url"
                      class="size-5 rounded"
                    />
                    <span class="truncate text-sm text-secondary">
                      {{ hoveredFolder.owner.display_name }}
                    </span>
                  </div>
                </div>
                <span
                  class="shrink-0 text-xs px-1.5 py-0.5 rounded bg-primary/10 border border-primary/20 text-primary"
                >
                  {{ hoveredFolder.signs_count }} signs
                </span>
              </div>

              <div v-if="hoveredFolder.preview_signs.length > 0" class="mt-4">
                <PreviewSignGrid
                  :signs="hoveredFolder.preview_signs"
                  :folder-slug="hoveredFolder.slug"
                  :total-signs="hoveredFolder.signs_count"
                  :background-preset="hoveredFolder.preview_grid_background_preset"
                />
              </div>
              <p v-else class="mt-4 text-sm text-zinc-500">No signs in this folder.</p>
            </div>
          </Transition>
        </div>
      </div>

      <!-- <lg: mobile fallback grid -->
      <div class="grid gap-4 sm:grid-cols-2 lg:hidden">
        <ExploreFolderCard v-for="folder in folders" :key="folder.id" :folder="folder" />
      </div>
    </div>

    <nav
      v-if="meta && meta.last_page > 1"
      class="mt-10 flex items-center justify-center gap-2 lg:hidden"
    >
      <UiButton
        variant="secondary"
        :disabled="meta.current_page <= 1"
        @click="goToPage(meta.current_page - 1)"
      >
        Previous
      </UiButton>

      <button
        v-for="p in visiblePages"
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
  </div>
</template>

<style scoped>
.preview-panel-enter-active {
  transition: opacity 0.22s ease-out;
}

.preview-panel-leave-active {
  transition: opacity 0.16s ease-in;
}

.preview-panel-enter-from,
.preview-panel-leave-to {
  opacity: 0;
}
</style>
