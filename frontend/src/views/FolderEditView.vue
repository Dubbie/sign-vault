<script setup lang="ts">
import { computed, onMounted, reactive, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useFoldersStore } from '@/stores/folders'
import type { FolderVisibility, UpdateFolderPayload } from '@/types/folder'

import UiCard from '@/components/ui/UiCard.vue'
import UiEyebrow from '@/components/ui/UiEyebrow.vue'
import UiErrorBanner from '@/components/ui/UiErrorBanner.vue'
import UiFormField from '@/components/ui/UiFormField.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiButton from '@/components/ui/UiButton.vue'

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

const visibilityOptions = [
  { value: 'private', label: 'Private' },
  { value: 'public', label: 'Public' },
  { value: 'password', label: 'Password' },
]

function fillFormFromFolder() {
  const folder = foldersStore.currentFolder
  if (!folder) return

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
  if (folder) fillFormFromFolder()
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
  <UiCard max-width="40rem">
    <UiEyebrow>Folders</UiEyebrow>
    <h1 class="text-[clamp(2rem,4vw,2.5rem)] text-zinc-100">Edit folder</h1>

    <UiErrorBanner v-if="foldersStore.error">
      {{ foldersStore.error }}
    </UiErrorBanner>

    <p v-if="foldersStore.isLoading && !foldersStore.currentFolder" class="mt-4 text-zinc-400">
      Loading folder...
    </p>

    <form v-else class="mt-5 grid gap-4" @submit.prevent="handleSubmit">
      <UiFormField label="Name" name="name">
        <UiInput v-model="form.name" type="text" name="name" required />
      </UiFormField>

      <UiFormField label="Visibility" name="visibility">
        <UiSelect v-model="form.visibility" name="visibility" :options="visibilityOptions" />
      </UiFormField>

      <UiFormField v-if="requiresPassword" label="Password" name="password">
        <UiInput
          v-model="form.password"
          type="password"
          name="password"
          placeholder="Leave blank to keep the current password"
        />
      </UiFormField>

      <div class="flex gap-3">
        <UiButton variant="primary" type="submit" :disabled="foldersStore.isLoading">
          {{ foldersStore.isLoading ? 'Saving...' : 'Save changes' }}
        </UiButton>
      </div>
    </form>
  </UiCard>
</template>
