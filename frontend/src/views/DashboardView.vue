<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'
import { useFoldersStore } from '@/stores/folders'

import CreateFolderModal from '@/components/folders/CreateFolderModal.vue'
import UiButton from '@/components/ui/UiButton.vue'
import { Archive, Folder, Link, Plus } from '@lucide/vue'
import StatCard from '@/components/ui/StatCard.vue'
import SmallFolderCard from '@/components/folders/SmallFolderCard.vue'

const auth = useAuthStore()
const foldersStore = useFoldersStore()
const router = useRouter()

const showCreateModal = ref(false)

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

function handleCreateSaved(folderId: number) {
  router.push({ name: 'folders-show', params: { id: folderId } })
}
</script>

<template>
  <div class="mx-auto max-w-7xl space-y-gutter">
    <div class="flex items-start justify-between gap-4">
      <h1 class="text-headline-xl text-on-surface">
        Welcome back,
        {{ auth.user?.discord_global_name ?? auth.user?.discord_username ?? 'Dude' }}
      </h1>

      <div>
        <UiButton @click="showCreateModal = true">
          <Plus class="size-5" />
          Create folder
        </UiButton>
      </div>
    </div>

    <div class="grid gap-6 sm:grid-cols-4">
      <StatCard :icon="Archive" label="Total signs" value="12 842" />

      <StatCard :icon="Archive" label="Signs by you" value="100" />

      <StatCard
        :icon="Folder"
        label="Folders owned"
        :value="foldersStore.folderCount.toLocaleString()"
      />

      <StatCard :icon="Link" label="Signs copied" value="1 234" />
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
