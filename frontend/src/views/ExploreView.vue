<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { getPublicFolders } from '@/lib/public-folders'
import type { PaginationMeta, PublicFolderListing } from '@/types/public-folder'

type SortOption = 'latest' | 'votes'

import UiInput from '@/components/ui/UiInput.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiErrorBanner from '@/components/ui/UiErrorBanner.vue'
import ExploreFolderCard from '@/components/explore/ExploreFolderCard.vue'
import PreviewSignGrid from '@/components/explore/PreviewSignGrid.vue'

const route = useRoute()
const router = useRouter()

const folders = ref<PublicFolderListing[]>([])
const isLoading = ref(true)
const error = ref<string | null>(null)
const search = ref(String(route.query.q || ''))
const SORT_STORAGE_KEY = 'explore:sort'
const storedSort = localStorage.getItem(SORT_STORAGE_KEY) as SortOption | null
const sort = ref<SortOption>((route.query.sort as SortOption) || storedSort || 'latest')
const meta = ref<PaginationMeta | null>(null)
const hoveredFolder = ref<PublicFolderListing | null>(null)

async function loadFolders(page = 1) {
  isLoading.value = true
  error.value = null

  try {
    const params: { q?: string; page?: number; sort?: SortOption } = { page }
    if (search.value) params.q = search.value
    if (sort.value !== 'latest') params.sort = sort.value

    const response = await getPublicFolders(params)
    folders.value = response.data
    meta.value = response.meta
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
  if (sort.value !== 'latest') query.sort = sort.value
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
    <div class="flex items-start justify-between gap-4">
      <div>
        <h1 class="text-headline-xl text-on-surface">Explore</h1>
      </div>
    </div>

    <div class="mt-6 flex gap-3">
      <UiInput
        class="flex-1"
        :model-value="search"
        placeholder="Search folders by name..."
        @update:model-value="handleSearchInput"
      />
      <div class="flex rounded-lg border border-outline/30 overflow-hidden text-sm font-medium shrink-0">
        <button
          type="button"
          class="px-3 py-2 transition-colors"
          :class="sort === 'latest' ? 'bg-primary/10 text-primary' : 'text-on-surface-variant hover:text-on-surface'"
          @click="handleSortChange('latest')"
        >
          Latest
        </button>
        <button
          type="button"
          class="px-3 py-2 transition-colors border-l border-outline/30"
          :class="sort === 'votes' ? 'bg-primary/10 text-primary' : 'text-on-surface-variant hover:text-on-surface'"
          @click="handleSortChange('votes')"
        >
          Top
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
      <div class="hidden lg:grid gap-6" style="grid-template-columns: 350px 1fr">
        <div class="flex flex-col gap-2">
          <ExploreFolderCard
            v-for="folder in folders"
            :key="folder.id"
            :folder="folder"
            :active="hoveredFolder === folder"
            @mouseenter="hoveredFolder = folder"
          />
        </div>

        <div>
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
                    <img
                      v-if="hoveredFolder.owner.avatar_url"
                      :src="hoveredFolder.owner.avatar_url"
                      :alt="hoveredFolder.owner.display_name"
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
