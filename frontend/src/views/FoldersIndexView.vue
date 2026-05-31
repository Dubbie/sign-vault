<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import { useFoldersStore } from '@/stores/folders'

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

  if (!confirmed) {
    return
  }

  await foldersStore.deleteFolder(id)
}

function handleCreate() {
  router.push({ name: 'folders-new' })
}
</script>

<template>
  <section class="page-card">
    <div class="header">
      <div>
        <p class="eyebrow">Folders</p>
        <h1>Folders</h1>
      </div>

      <button class="primary-button" type="button" @click="handleCreate">
        Create folder
      </button>
    </div>

    <p v-if="foldersStore.error" class="error-banner">
      {{ foldersStore.error }}
    </p>

    <p v-if="foldersStore.isLoading" class="muted">Loading folders...</p>

    <p v-else-if="foldersStore.folders.length === 0" class="empty-state">
      No folders yet.
    </p>

    <div v-else class="folder-grid">
      <article v-for="folder in foldersStore.folders" :key="folder.id" class="folder-card">
        <div class="folder-top">
          <div>
            <h2>{{ folder.name }}</h2>
            <p class="slug">{{ folder.slug }}</p>
          </div>

          <span class="badge">{{ visibilityLabel(folder.visibility) }}</span>
        </div>

        <dl class="meta">
          <div>
            <dt>Created</dt>
            <dd>{{ formatDate(folder.created_at) }}</dd>
          </div>
        </dl>

        <div class="actions">
          <RouterLink class="text-link" :to="{ name: 'folders-show', params: { id: folder.id } }">
            View
          </RouterLink>
          <RouterLink class="text-link" :to="{ name: 'folders-edit', params: { id: folder.id } }">
            Edit
          </RouterLink>
          <button class="danger-button" type="button" @click="handleDelete(folder.id)">
            Delete
          </button>
        </div>
      </article>
    </div>
  </section>
</template>

<style scoped>
.page-card {
  width: min(100%, 60rem);
  padding: 2rem;
  border: 1px solid var(--color-border);
  border-radius: 1.5rem;
  background: var(--color-surface);
  box-shadow: var(--shadow-elevated);
  backdrop-filter: blur(18px);
}

.header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.eyebrow {
  margin-bottom: 0.5rem;
  color: var(--color-primary);
  font-size: 0.85rem;
  font-weight: 600;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}

h1 {
  color: var(--color-heading);
  font-size: clamp(2rem, 4vw, 2.5rem);
  line-height: 1.05;
}

.primary-button {
  padding: 0.85rem 1rem;
  border: 0;
  border-radius: 0.9rem;
  background: linear-gradient(135deg, var(--color-primary), var(--color-primary-strong));
  color: #04111e;
  font-weight: 700;
  cursor: pointer;
}

.error-banner {
  margin-top: 1rem;
  padding: 0.9rem 1rem;
  border: 1px solid rgba(251, 113, 133, 0.35);
  border-radius: 0.9rem;
  color: #fecdd3;
  background: rgba(127, 29, 29, 0.24);
}

.muted,
.empty-state {
  margin-top: 1rem;
  color: var(--color-text-muted);
}

.folder-grid {
  display: grid;
  gap: 1rem;
  margin-top: 1.25rem;
}

.folder-card {
  padding: 1rem;
  border: 1px solid var(--color-border);
  border-radius: 1rem;
  background: var(--color-surface-strong);
}

.folder-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

h2 {
  color: var(--color-heading);
  font-size: 1.15rem;
}

.slug {
  color: var(--color-text-muted);
}

.badge {
  padding: 0.3rem 0.65rem;
  border-radius: 999px;
  border: 1px solid var(--color-border);
  color: var(--color-heading);
  font-size: 0.85rem;
  text-transform: capitalize;
}

.meta {
  margin-top: 1rem;
}

.meta dt {
  color: var(--color-text-muted);
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.meta dd {
  margin-top: 0.25rem;
  color: var(--color-heading);
}

.actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-top: 1rem;
}

.text-link,
.danger-button {
  padding: 0.6rem 0.9rem;
  border-radius: 0.8rem;
  border: 1px solid var(--color-border);
  background: transparent;
  color: var(--color-heading);
  text-decoration: none;
  cursor: pointer;
}

.danger-button {
  border-color: rgba(251, 113, 133, 0.35);
  color: #fecdd3;
}
</style>
