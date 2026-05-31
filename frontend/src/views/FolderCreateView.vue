<script setup lang="ts">
import { computed, onMounted, reactive, watch } from 'vue'
import { useRouter } from 'vue-router'

import { useFoldersStore } from '@/stores/folders'
import type { CreateFolderPayload, FolderVisibility } from '@/types/folder'

const foldersStore = useFoldersStore()
const router = useRouter()

const form = reactive({
  name: '',
  visibility: 'private' as FolderVisibility,
  password: '',
})

const requiresPassword = computed(() => form.visibility === 'password')

onMounted(() => {
  foldersStore.clearError()
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

  if (requiresPassword.value && !form.password.trim()) {
    foldersStore.error = 'Password is required for password-protected folders.'
    return
  }

  const payload: CreateFolderPayload = {
    name: form.name.trim(),
    visibility: form.visibility,
  }

  if (requiresPassword.value) {
    payload.password = form.password
  }

  const folder = await foldersStore.createFolder(payload)

  if (folder) {
    await router.push({ name: 'folders-show', params: { id: folder.id } })
  }
}
</script>

<template>
  <section class="page-card">
    <p class="eyebrow">Folders</p>
    <h1>Create folder</h1>

    <p v-if="foldersStore.error" class="error-banner">
      {{ foldersStore.error }}
    </p>

    <form class="form" @submit.prevent="handleSubmit">
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
        <input v-model="form.password" type="password" name="password" />
      </label>

      <div class="actions">
        <button class="primary-button" type="submit" :disabled="foldersStore.isLoading">
          {{ foldersStore.isLoading ? 'Saving...' : 'Create folder' }}
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
