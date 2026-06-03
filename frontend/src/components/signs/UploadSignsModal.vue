<script setup lang="ts">
import { computed, ref } from 'vue'

import type { CreateSignPayload } from '@/types/sign'
import { useAuthStore } from '@/stores/auth'
import { useSignsStore } from '@/stores/signs'

import UiModal from '@/components/ui/UiModal.vue'
import UiErrorBanner from '@/components/ui/UiErrorBanner.vue'
import UiFormField from '@/components/ui/UiFormField.vue'
import UiButton from '@/components/ui/UiButton.vue'

const props = defineProps<{
  modelValue: boolean
  folderId: number
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  saved: []
}>()

const authStore = useAuthStore()
const signsStore = useSignsStore()
const maxFiles = computed(() => authStore.signUploadMaxFiles)

const selectedFiles = ref<File[]>([])
const fileInput = ref<HTMLInputElement | null>(null)

const allowedMimeTypes = new Set(['image/png', 'image/jpeg', 'image/webp'])

function resetForm() {
  selectedFiles.value = []
  if (fileInput.value) fileInput.value.value = ''
}

function validateSelectedFiles(files: File[]) {
  if (files.length === 0) return 'At least one image file is required.'
  if (files.length > maxFiles.value) {
    return `You may upload at most ${maxFiles.value} files at a time.`
  }
  const invalidFile = files.find((file) => !allowedMimeTypes.has(file.type))
  if (invalidFile) return 'Files must be PNG, JPEG, or WebP images.'
  return null
}

function close() {
  emit('update:modelValue', false)
  resetForm()
  signsStore.clearError()
}

function handleFileChange(event: Event) {
  const input = event.target as HTMLInputElement
  const files = Array.from(input.files ?? [])
  signsStore.clearError()

  const fileError = validateSelectedFiles(files)
  if (fileError) {
    selectedFiles.value = []
    input.value = ''
    signsStore.error = fileError
    return
  }

  selectedFiles.value = files
}

async function handleSubmit() {
  signsStore.clearError()

  const fileError = validateSelectedFiles(selectedFiles.value)
  if (fileError) {
    signsStore.error = fileError
    return
  }

  const payload: CreateSignPayload = { files: selectedFiles.value }
  const uploadedSigns = await signsStore.uploadSign(props.folderId, payload)

  if (uploadedSigns) {
    emit('saved')
    close()
  }
}
</script>

<template>
  <UiModal :model-value="modelValue" title="Upload new signs" @update:model-value="close">
    <UiErrorBanner v-if="signsStore.error">
      {{ signsStore.error }}
    </UiErrorBanner>

    <form @submit.prevent="handleSubmit">
      <UiFormField label="Files" name="file">
        <input
          ref="fileInput"
          type="file"
          name="file"
          multiple
          accept="image/png,image/jpeg,image/webp"
          required
          class="w-full rounded bg-surface text-sm p-2 text-zinc-100 file:mr-3 file:cursor-pointer file:rounded-sm file:border-0 file:bg-white file:px-3 file:py-1 file:text-xs file:font-semibold file:text-background"
          @change="handleFileChange"
        />
      </UiFormField>

      <div v-if="selectedFiles.length" class="mt-2 text-sm text-zinc-400">
        <p class="font-semibold">Selected:</p>
        <p class="ml-3">{{ selectedFiles.map((file) => file.name).join(', ') }}</p>
      </div>

      <p class="mt-1 text-xs text-zinc-400">PNG, JPEG, or WebP</p>
      <p class="mt-1 text-xs text-zinc-400">Up to {{ maxFiles }} files per upload.</p>

      <div class="mt-6 flex flex-wrap gap-3">
        <UiButton variant="primary" type="submit" :disabled="signsStore.isUploading">
          {{ signsStore.isUploading ? 'Uploading...' : 'Upload' }}
        </UiButton>
        <UiButton variant="secondary" type="button" @click="close"> Cancel </UiButton>
      </div>
    </form>
  </UiModal>
</template>
