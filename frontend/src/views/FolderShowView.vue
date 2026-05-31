<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import { useFoldersStore } from '@/stores/folders'

const foldersStore = useFoldersStore()
const route = useRoute()
const router = useRouter()

const folderId = computed(() => Number(route.params.id))
const folder = computed(() => foldersStore.currentFolder)

const dateFormatter = new Intl.DateTimeFormat(undefined, {
  dateStyle: 'medium',
  timeStyle: 'short',
})

function formatDate(value: string) {
  return dateFormatter.format(new Date(value))
}

function visibilityLabel(visibility: string) {
  return visibility.charAt(0).toUpperCase() + visibility.slice(1)
}

async function loadFolder() {
  const id = folderId.value

  if (!Number.isFinite(id)) {
    foldersStore.error = 'Invalid folder id.'
    return
  }

  await foldersStore.fetchFolder(id)
}

onMounted(loadFolder)

watch(folderId, () => {
  foldersStore.clearCurrentFolder()
  void loadFolder()
})

function goBack() {
  router.push({ name: 'folders' })
}
</script>

<template>
  <section class="page-card">
    <RouterLink class="back-link" to="/folders">Back to folders</RouterLink>

    <p class="eyebrow">Folder details</p>

    <p v-if="foldersStore.error" class="error-banner">
      {{ foldersStore.error }}
    </p>

    <p v-if="foldersStore.isLoading" class="muted">Loading folder...</p>

    <div v-else-if="folder" class="details">
      <div class="header">
        <div>
          <h1>{{ folder.name }}</h1>
          <p class="slug">{{ folder.slug }}</p>
        </div>

        <span class="badge">{{ visibilityLabel(folder.visibility) }}</span>
      </div>

      <dl class="meta">
        <div>
          <dt>Created</dt>
          <dd>{{ formatDate(folder.created_at) }}</dd>
        </div>
        <div>
          <dt>Updated</dt>
          <dd>{{ formatDate(folder.updated_at) }}</dd>
        </div>
      </dl>

      <div class="actions">
        <RouterLink class="primary-link" :to="{ name: 'folders-edit', params: { id: folder.id } }">
          Edit folder
        </RouterLink>
        <button class="secondary-button" type="button" @click="goBack">
          Back
        </button>
      </div>
    </div>
  </section>
</template>

<style scoped>
.page-card {
  width: min(100%, 40rem);
  padding: 2rem;
  border: 1px solid var(--color-border);
  border-radius: 1.5rem;
  background: var(--color-surface);
  box-shadow: var(--shadow-elevated);
  backdrop-filter: blur(18px);
}

.back-link {
  color: var(--color-primary);
  text-decoration: none;
}

.eyebrow {
  margin-top: 1rem;
  margin-bottom: 0.5rem;
  color: var(--color-primary);
  font-size: 0.85rem;
  font-weight: 600;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}

.error-banner {
  margin-top: 1rem;
  padding: 0.9rem 1rem;
  border: 1px solid rgba(251, 113, 133, 0.35);
  border-radius: 0.9rem;
  color: #fecdd3;
  background: rgba(127, 29, 29, 0.24);
}

.muted {
  margin-top: 1rem;
  color: var(--color-text-muted);
}

.header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

h1 {
  color: var(--color-heading);
  font-size: clamp(2rem, 4vw, 2.5rem);
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
  display: grid;
  gap: 1rem;
  margin-top: 1.25rem;
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
  margin-top: 1.5rem;
}

.primary-link,
.secondary-button {
  padding: 0.8rem 1rem;
  border-radius: 0.9rem;
  text-decoration: none;
  cursor: pointer;
}

.primary-link {
  background: linear-gradient(135deg, var(--color-primary), var(--color-primary-strong));
  color: #04111e;
  font-weight: 700;
}

.secondary-button {
  border: 1px solid var(--color-border);
  background: transparent;
  color: var(--color-heading);
}
</style>
