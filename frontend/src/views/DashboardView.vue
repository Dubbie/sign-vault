<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'
import { useFoldersStore } from '@/stores/folders'

import UiBadge from '@/components/ui/UiBadge.vue'
import CreateFolderModal from '@/components/folders/CreateFolderModal.vue'

const auth = useAuthStore()
const foldersStore = useFoldersStore()
const router = useRouter()

const showCreateModal = ref(false)

const dateFormatter = new Intl.DateTimeFormat(undefined, {
  dateStyle: 'medium',
  timeStyle: 'short',
})

const recentFolders = computed(() => foldersStore.folders.slice(0, 5))

onMounted(async () => {
  if (!auth.isAuthenticated) {
    await router.replace({ name: 'login' })
    return
  }

  if (foldersStore.folders.length === 0) {
    await foldersStore.fetchFolders()
  }
})

function visibilityLabel(visibility: string) {
  return visibility.charAt(0).toUpperCase() + visibility.slice(1)
}

function formatDate(value: string) {
  return dateFormatter.format(new Date(value))
}

function handleCreateSaved() {
  const folder = foldersStore.currentFolder
  if (!folder) return
  router.push({ name: 'folders-show', params: { id: folder.id } })
}
</script>

<template>
  <div class="mx-auto max-w-7xl">
    <div class="mb-8">
      <h1 class="text-[clamp(1.5rem,3vw,2rem)] text-zinc-500 font-normal">Welcome back</h1>
      <p class="text-[clamp(2rem,4vw,2.5rem)] leading-tight text-zinc-100 font-bold">
        {{ auth.user?.discord_global_name ?? auth.user?.discord_username ?? 'Dude' }}
      </p>
    </div>

    <div class="grid gap-6 sm:grid-cols-3">
      <div class="rounded-2xl ring ring-white/20 bg-zinc-900/50 p-6">
        <p class="text-3xl font-bold text-zinc-100">{{ foldersStore.folderCount }}</p>
        <p class="mt-1 text-sm text-zinc-400">Folders</p>
      </div>

      <RouterLink
        to="/folders"
        class="rounded-2xl ring ring-white/20 bg-zinc-900/50 p-6 no-underline transition hover:border-emerald-400/50 hover:bg-zinc-900"
      >
        <p class="text-xl font-semibold text-zinc-100">View your folders</p>
        <p class="mt-1 text-sm text-zinc-400">Browse and manage your sign folders</p>
      </RouterLink>

      <button
        type="button"
        class="rounded-2xl bg-emerald-400 p-6 text-left no-underline transition hover:bg-emerald-200 cursor-pointer"
        @click="showCreateModal = true"
      >
        <p class="text-xl font-semibold text-background">Create a folder</p>
        <p class="mt-1 text-sm text-background">Start a new sign collection</p>
      </button>
    </div>

    <div v-if="recentFolders.length" class="mt-10">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-zinc-100">Recent folders</h2>
        <RouterLink
          to="/folders"
          class="text-sm text-emerald-400 underline-offset-2 hover:underline"
        >
          View all folders &rarr;
        </RouterLink>
      </div>

      <div class="mt-4 grid gap-3">
        <RouterLink
          v-for="folder in recentFolders"
          :key="folder.id"
          :to="{ name: 'folders-show', params: { id: folder.id } }"
          class="flex items-center justify-between rounded-xl border border-white/20 bg-zinc-900/50 px-5 py-4 no-underline transition hover:border-white/20 hover:bg-zinc-900"
        >
          <div class="min-w-0">
            <p class="truncate font-semibold text-zinc-100">{{ folder.name }}</p>
            <p class="truncate font-mono text-xs text-zinc-500">{{ folder.slug }}</p>
          </div>

          <div class="flex items-center gap-3 shrink-0">
            <UiBadge :label="visibilityLabel(folder.visibility)" />
            <span class="text-xs text-zinc-500 max-md:hidden">{{
              formatDate(folder.created_at)
            }}</span>
          </div>
        </RouterLink>
      </div>
    </div>

    <div
      v-else-if="!foldersStore.isLoading"
      class="mt-10 rounded-2xl border border-dashed border-white/10 p-10 text-center"
    >
      <p class="text-zinc-400">No folders yet</p>
      <button
        type="button"
        class="mt-2 inline-flex cursor-pointer text-sm text-emerald-400 underline-offset-2 hover:underline"
        @click="showCreateModal = true"
      >
        Create your first folder
      </button>
    </div>

    <CreateFolderModal v-model="showCreateModal" @saved="handleCreateSaved" />
  </div>
</template>
