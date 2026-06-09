<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import { getAppStats } from '@/lib/stats'
import { useAuthStore } from '@/stores/auth'
import { useFoldersStore } from '@/stores/folders'

import CreateFolderModal from '@/components/folders/CreateFolderModal.vue'
import UiButton from '@/components/ui/UiButton.vue'
import { Archive, Folder, Link, Monitor, Plus } from '@lucide/vue'
import StatCard from '@/components/ui/StatCard.vue'
import SmallFolderCard from '@/components/folders/SmallFolderCard.vue'

const auth = useAuthStore()
const foldersStore = useFoldersStore()
const router = useRouter()

const showCreateModal = ref(false)
const totalSigns = ref(0)

const recentFolders = computed(() => foldersStore.folders.slice(0, 5))
const publicFolderCount = computed(
  () => foldersStore.folders.filter((folder) => folder.visibility === 'public').length,
)
const userSignCount = computed(() => auth.user?.signs_count ?? 0)

async function fetchDashboardStats() {
  try {
    const stats = await getAppStats()
    totalSigns.value = stats.total_signs
  } catch {
    totalSigns.value = 0
  }
}

onMounted(async () => {
  if (!auth.isAuthenticated) {
    await router.replace({ name: 'login' })
    return
  }

  const requests: Promise<unknown>[] = [fetchDashboardStats()]

  if (foldersStore.folders.length === 0) {
    requests.push(foldersStore.fetchFolders())
  }

  await Promise.all(requests)
})

function handleCreateSaved(folderId: number) {
  router.push({ name: 'folders-show', params: { id: folderId } })
}
</script>

<template>
  <div class="mx-auto max-w-7xl space-y-gutter">
    <div class="flex items-end justify-between gap-4">
      <h1 class="text-headline-xl text-on-surface">
        Welcome back,
        {{ auth.user?.display_name ?? 'there' }}
      </h1>

      <UiButton @click="showCreateModal = true">
        <Plus class="size-5" />
        Create folder
      </UiButton>
    </div>

    <div class="grid gap-6 sm:grid-cols-4">
      <StatCard :icon="Archive" label="Total signs" :value="totalSigns.toLocaleString()" />

      <StatCard :icon="Monitor" label="Signs by you" :value="userSignCount.toLocaleString()" />

      <StatCard
        :icon="Folder"
        label="Folders owned"
        :value="foldersStore.folderCount.toLocaleString()"
      />

      <StatCard
        :icon="Link"
        label="Your public folders"
        :value="publicFolderCount.toLocaleString()"
      />
    </div>

    <div v-if="recentFolders.length" class="space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-headline-md">Recent folders</h2>
        <RouterLink to="/folders" class="cursor-pointer text-primary text-sm hover:underline">
          View all
        </RouterLink>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-gutter">
        <SmallFolderCard v-for="folder in recentFolders" :key="folder.id" :folder="folder" />
      </div>
    </div>

    <div
      v-else-if="!foldersStore.isLoading"
      class="rounded-2xl border border-dashed border-outline-variant p-10 text-center space-y-4"
    >
      <p class="text-on-surface text-headline-md">You haven't created any folders yet</p>

      <UiButton variant="secondary" @click="showCreateModal = true">
        <Plus class="size-5" />
        Create your first folder
      </UiButton>
    </div>

    <CreateFolderModal v-model="showCreateModal" @saved="handleCreateSaved" />
  </div>
</template>
