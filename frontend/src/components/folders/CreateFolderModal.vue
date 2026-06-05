<script setup lang="ts">
import { computed, reactive, watch } from 'vue'

import { useFoldersStore } from '@/stores/folders'
import type { CreateFolderPayload, FolderVisibility } from '@/types/folder'

import UiModal from '@/components/ui/UiModal.vue'
import UiErrorBanner from '@/components/ui/UiErrorBanner.vue'
import UiFormField from '@/components/ui/UiFormField.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiButton from '@/components/ui/UiButton.vue'

defineProps<{
  modelValue: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  saved: [folderId: number]
}>()

const foldersStore = useFoldersStore()

const form = reactive({
  name: '',
  visibility: 'private' as FolderVisibility,
  password: '',
  attributionName: '',
  attributionSourceUrl: '',
})

const requiresPassword = computed(() => form.visibility === 'password')

const visibilityOptions = [
  { value: 'private', label: 'Private' },
  { value: 'public', label: 'Public' },
  { value: 'password', label: 'Password' },
]

watch(requiresPassword, (next) => {
  if (!next) form.password = ''
})

function resetForm() {
  form.name = ''
  form.visibility = 'private'
  form.password = ''
  form.attributionName = ''
  form.attributionSourceUrl = ''
}

function close() {
  emit('update:modelValue', false)
  resetForm()
  foldersStore.clearError()
}

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
    attribution_name: form.attributionName.trim() || undefined,
    attribution_source_url: form.attributionSourceUrl.trim() || undefined,
  }

  if (requiresPassword.value) {
    payload.password = form.password
  }

  const folder = await foldersStore.createFolder(payload)

  if (folder) {
    emit('saved', folder.id)
    close()
  }
}
</script>

<template>
  <UiModal :model-value="modelValue" title="Create folder" @update:model-value="close">
    <UiErrorBanner v-if="foldersStore.error">
      {{ foldersStore.error }}
    </UiErrorBanner>

    <form class="grid gap-4" @submit.prevent="handleSubmit">
      <UiFormField label="Name" name="name">
        <UiInput v-model="form.name" type="text" name="name" required />
      </UiFormField>

      <UiFormField label="Visibility" name="visibility">
        <UiSelect v-model="form.visibility" name="visibility" :options="visibilityOptions" />
      </UiFormField>

      <UiFormField v-if="requiresPassword" label="Password" name="password">
        <UiInput v-model="form.password" type="password" name="password" />
      </UiFormField>

      <UiFormField label="Original author" name="attribution_name">
        <UiInput
          v-model="form.attributionName"
          type="text"
          name="attribution_name"
          placeholder="e.g. Buried, xXTrackMakerXx"
        />
      </UiFormField>

      <UiFormField label="Source URL" name="attribution_source_url">
        <UiInput
          v-model="form.attributionSourceUrl"
          type="url"
          name="attribution_source_url"
          placeholder="https://..."
        />
      </UiFormField>

      <div class="flex gap-3">
        <UiButton variant="primary" type="submit" :disabled="foldersStore.isLoading">
          {{ foldersStore.isLoading ? 'Saving...' : 'Create folder' }}
        </UiButton>
      </div>
    </form>
  </UiModal>
</template>
