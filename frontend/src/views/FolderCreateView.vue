<script setup lang="ts">
import { computed, onMounted, reactive, watch } from 'vue'
import { useRouter } from 'vue-router'

import { useFoldersStore } from '@/stores/folders'
import type { CreateFolderPayload, FolderVisibility } from '@/types/folder'

import UiCard from '@/components/ui/UiCard.vue'
import UiEyebrow from '@/components/ui/UiEyebrow.vue'
import UiErrorBanner from '@/components/ui/UiErrorBanner.vue'
import UiFormField from '@/components/ui/UiFormField.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiButton from '@/components/ui/UiButton.vue'

const foldersStore = useFoldersStore()
const router = useRouter()

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
    attribution_name: form.attributionName.trim() || undefined,
    attribution_source_url: form.attributionSourceUrl.trim() || undefined,
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
  <UiCard max-width="40rem">
    <UiEyebrow>Folders</UiEyebrow>
    <h1 class="text-[clamp(2rem,4vw,2.5rem)] text-zinc-100">Create folder</h1>

    <UiErrorBanner v-if="foldersStore.error">
      {{ foldersStore.error }}
    </UiErrorBanner>

    <form class="mt-5 grid gap-4" @submit.prevent="handleSubmit">
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
  </UiCard>
</template>
