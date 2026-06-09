<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'

import {
  emptyEditableFolderAuthor,
  toEditableFolderAuthors,
  toFolderAuthorPayload,
} from '@/lib/folder-authors'
import { useFoldersStore } from '@/stores/folders'
import type { FolderVisibility, UpdateFolderPayload } from '@/types/folder'

import FolderAuthorsFields from '@/components/folders/FolderAuthorsFields.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiErrorBanner from '@/components/ui/UiErrorBanner.vue'
import UiFormField from '@/components/ui/UiFormField.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiButton from '@/components/ui/UiButton.vue'

const props = defineProps<{
  modelValue: boolean
  folderId: number
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  saved: []
}>()

const foldersStore = useFoldersStore()

const isFolderReady = ref(false)
const form = reactive({
  name: '',
  visibility: 'private' as FolderVisibility,
  password: '',
  authors: [emptyEditableFolderAuthor()],
})

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
  form.authors = toEditableFolderAuthors(folder.authors)
}

async function loadFolder(folderId: number) {
  if (!Number.isFinite(folderId)) {
    foldersStore.error = 'Invalid folder id.'
    return false
  }

  const folder = await foldersStore.fetchFolder(folderId)
  if (folder) {
    fillFormFromFolder()
    return true
  }

  return false
}

watch(
  [() => props.modelValue, () => props.folderId],
  async ([open, folderId]) => {
    if (!open) return

    isFolderReady.value = false
    foldersStore.clearError()

    if (foldersStore.currentFolder?.id === folderId) {
      fillFormFromFolder()
      isFolderReady.value = true
      return
    }

    isFolderReady.value = await loadFolder(folderId)
  },
  { immediate: true },
)

watch(requiresPassword, (next) => {
  if (!next) form.password = ''
})

function close() {
  emit('update:modelValue', false)
}

async function handleSubmit() {
  foldersStore.clearError()

  if (!form.name.trim()) {
    foldersStore.error = 'Name is required.'
    return
  }

  const payload: UpdateFolderPayload = {
    name: form.name.trim(),
    visibility: form.visibility,
    authors: toFolderAuthorPayload(form.authors),
  }

  if (requiresPassword.value && form.password.trim()) {
    payload.password = form.password
  }

  const folder = await foldersStore.updateFolder(props.folderId, payload)

  if (folder) {
    emit('saved')
    close()
  }
}
</script>

<template>
  <UiModal :model-value="modelValue" title="Edit folder" @update:model-value="close">
    <UiErrorBanner v-if="foldersStore.error">
      {{ foldersStore.error }}
    </UiErrorBanner>

    <p v-if="!isFolderReady" class="text-zinc-400">Loading folder...</p>

    <form v-else class="grid gap-4" @submit.prevent="handleSubmit">
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

      <FolderAuthorsFields v-model="form.authors" />

      <div class="flex gap-3">
        <UiButton variant="primary" type="submit" :disabled="foldersStore.isLoading">
          {{ foldersStore.isLoading ? 'Saving...' : 'Save changes' }}
        </UiButton>
      </div>
    </form>
  </UiModal>
</template>
