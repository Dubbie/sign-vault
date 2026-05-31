<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import { useFoldersStore } from '@/stores/folders'

import UiCard from '@/components/ui/UiCard.vue'
import UiEyebrow from '@/components/ui/UiEyebrow.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiErrorBanner from '@/components/ui/UiErrorBanner.vue'
import UiBadge from '@/components/ui/UiBadge.vue'

const foldersStore = useFoldersStore()
const router = useRouter()

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

async function handleDelete(id: number) {
  const confirmed = window.confirm('Delete this folder?')
  if (!confirmed) return
  await foldersStore.deleteFolder(id)
}

function handleCreate() {
  router.push({ name: 'folders-new' })
}
</script>

<template>
  <UiCard max-width="60rem">
    <div class="flex items-start justify-between gap-4">
      <div>
        <UiEyebrow>Folders</UiEyebrow>
        <h1 class="text-[clamp(2rem,4vw,2.5rem)] leading-tight text-heading">Folders</h1>
      </div>

      <UiButton variant="primary" type="button" @click="handleCreate">
        Create folder
      </UiButton>
    </div>

    <UiErrorBanner v-if="foldersStore.error">
      {{ foldersStore.error }}
    </UiErrorBanner>

    <p v-if="foldersStore.isLoading" class="mt-4 text-text-muted">Loading folders...</p>

    <p v-else-if="foldersStore.folders.length === 0" class="mt-4 text-text-muted">
      No folders yet.
    </p>

    <div v-else class="mt-5 grid gap-4">
      <article
        v-for="folder in foldersStore.folders"
        :key="folder.id"
        class="rounded-xl border border-border bg-surface-strong p-4"
      >
        <div class="flex items-start justify-between gap-4">
          <div>
            <h2 class="text-[1.15rem] text-heading">{{ folder.name }}</h2>
            <p class="text-text-muted">{{ folder.slug }}</p>
          </div>
          <UiBadge :label="visibilityLabel(folder.visibility)" />
        </div>

        <dl class="mt-4">
          <dt class="text-[0.8rem] uppercase tracking-[0.08em] text-text-muted">Created</dt>
          <dd class="mt-1 text-heading">{{ formatDate(folder.created_at) }}</dd>
        </dl>

        <div class="mt-4 flex flex-wrap gap-3">
          <RouterLink
            class="cursor-pointer rounded-xl border border-border bg-transparent px-[0.9rem] py-[0.6rem] text-heading no-underline transition duration-150 ease-in-out hover:bg-white/5"
            :to="{ name: 'folders-show', params: { id: folder.id } }"
          >
            View
          </RouterLink>
          <RouterLink
            class="cursor-pointer rounded-xl border border-border bg-transparent px-[0.9rem] py-[0.6rem] text-heading no-underline transition duration-150 ease-in-out hover:bg-white/5"
            :to="{ name: 'folders-edit', params: { id: folder.id } }"
          >
            Edit
          </RouterLink>
          <button
            type="button"
            class="cursor-pointer rounded-xl border border-border-danger bg-transparent px-[0.9rem] py-[0.6rem] text-danger-text transition duration-150 ease-in-out hover:bg-white/5"
            @click="handleDelete(folder.id)"
          >
            Delete
          </button>
        </div>
      </article>
    </div>
  </UiCard>
</template>
