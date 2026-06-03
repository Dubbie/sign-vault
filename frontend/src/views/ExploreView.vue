<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { getPublicFolders } from '@/lib/public-folders'
import type { PaginationMeta, PublicFolderListing } from '@/types/public-folder'

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
const meta = ref<PaginationMeta | null>(null)
const hoveredFolder = ref<PublicFolderListing | null>(null)

async function loadFolders(page = 1) {
  isLoading.value = true
  error.value = null

  try {
    const params: { q?: string; page?: number } = { page }
    if (search.value) params.q = search.value

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
    <div class="flex items-start justify-between gap-4">
      <div>
        <h1 class="text-[clamp(2rem,4vw,2.5rem)] leading-tight text-zinc-100">Explore</h1>
        <p class="mt-1 text-sm text-zinc-400">Discover public sign folders from the community</p>
      </div>
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
          <div
            v-if="!hoveredFolder"
            class="flex min-h-[288px] items-center justify-center rounded-xl border border-dashed border-white/10"
          >
            <p class="text-sm text-zinc-500">Hover over a folder to preview its signs</p>
          </div>

          <div v-else>
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0 flex-1">
                <h2 class="text-lg font-semibold text-zinc-100">{{ hoveredFolder.name }}</h2>
                <div v-if="hoveredFolder.owner" class="mt-1 flex items-center gap-2">
                  <img
                    v-if="hoveredFolder.owner.discord_avatar"
                    :src="hoveredFolder.owner.discord_avatar"
                    :alt="hoveredFolder.owner.discord_username"
                    class="size-5 rounded-full"
                  />
                  <span class="truncate text-sm text-zinc-400">
                    {{
                      hoveredFolder.owner.discord_global_name ||
                      hoveredFolder.owner.discord_username
                    }}
                  </span>
                </div>
              </div>
              <span
                class="shrink-0 rounded-full bg-emerald-400/10 px-2.5 py-1 text-xs font-semibold text-emerald-400"
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
