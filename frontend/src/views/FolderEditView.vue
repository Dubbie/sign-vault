<script setup lang="ts">
import { computed, onMounted, reactive, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useFoldersStore } from '@/stores/folders'
import type { FolderVisibility, UpdateFolderPayload } from '@/types/folder'

const foldersStore = useFoldersStore()
const route = useRoute()
const router = useRouter()

const form = reactive({
  name: '',
  visibility: 'private' as FolderVisibility,
  password: '',
})

const folderId = computed(() => Number(route.params.id))
const requiresPassword = computed(() => form.visibility === 'password')

function fillFormFromFolder() {
  const folder = foldersStore.currentFolder

  if (!folder) {
    return
  }

  form.name = folder.name
  form.visibility = folder.visibility
  form.password = ''
}

async function loadFolder() {
  const id = folderId.value

  if (!Number.isFinite(id)) {
    foldersStore.error = 'Invalid folder id.'
    return
  }

  const folder = await foldersStore.fetchFolder(id)

  if (folder) {
    fillFormFromFolder()
  }
}

onMounted(async () => {
  foldersStore.clearError()

  if (foldersStore.currentFolder?.id !== folderId.value) {
    await loadFolder()
    return
  }

  fillFormFromFolder()
})

watch(folderId, async () => {
  foldersStore.clearCurrentFolder()
  await loadFolder()
})

watch(requiresPassword, (nextRequiresPassword) => {
  if (!nextRequiresPassword) {
    form.password = ''
  }
})

async function handleSubmit() {
  foldersStore.clearError()

  if (!form.name.trim()) {
    foldersStore.error = 'Name is required.'
    return
  }

  const payload: UpdateFolderPayload = {
    name: form.name.trim(),
    visibility: form.visibility,
  }

  if (requiresPassword.value && form.password.trim()) {
    payload.password = form.password
  }

  const folder = await foldersStore.updateFolder(folderId.value, payload)

  if (folder) {
    await router.push({ name: 'folders-show', params: { id: folder.id } })
  }
}
</script>

<template>
  <section class="page-card">
    <p class="eyebrow">Folders</p>
    <h1>Edit folder</h1>

    <p v-if="foldersStore.error" class="error-banner">
      {{ foldersStore.error }}
    </p>

    <p v-if="foldersStore.isLoading && !foldersStore.currentFolder" class="muted">
      Loading folder...
    </p>

    <form v-else class="form" @submit.prevent="handleSubmit">
      <label>
        <span>Name</span>
        <input v-model="form.name" type="text" name="name" required />
      </label>

      <label>
        <span>Visibility</span>
        <select v-model="form.visibility" name="visibility">
          <option value="private">Private</option>
          <option value="public">Public</option>
          <option value="password">Password</option>
        </select>
      </label>

      <label v-if="requiresPassword">
        <span>Password</span>
        <input
          v-model="form.password"
          type="password"
          name="password"
          placeholder="Leave blank to keep the current password"
        />
      </label>

      <div class="actions">
        <button class="primary-button" type="submit" :disabled="foldersStore.isLoading">
          {{ foldersStore.isLoading ? 'Saving...' : 'Save changes' }}
        </button>
      </div>
    </form>
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

.form {
  display: grid;
  gap: 1rem;
  margin-top: 1.25rem;
}

label {
  display: grid;
  gap: 0.4rem;
}

span {
  color: var(--color-text-muted);
  font-size: 0.9rem;
}

input,
select {
  width: 100%;
  padding: 0.85rem 1rem;
  border: 1px solid var(--color-border);
  border-radius: 0.9rem;
  background: var(--color-surface-strong);
  color: var(--color-heading);
}

.actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 0.25rem;
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
</style>
