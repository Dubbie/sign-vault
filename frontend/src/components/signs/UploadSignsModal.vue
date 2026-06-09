<script setup lang="ts">
import { computed, ref } from 'vue'

import type { CreateSignPayload } from '@/types/sign'
import type { Variant } from '@/types/folder'
import { useAuthStore } from '@/stores/auth'
import { useSignsStore } from '@/stores/signs'

import UiModal from '@/components/ui/UiModal.vue'
import UiErrorBanner from '@/components/ui/UiErrorBanner.vue'
import UiFormField from '@/components/ui/UiFormField.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiSelect from '@/components/ui/UiSelect.vue'

const props = defineProps<{
  modelValue: boolean
  folderId: number
  variants?: Variant[]
  selectedVariantId?: number | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  saved: []
}>()

const MAX_SELECTABLE_FILES = 300

const authStore = useAuthStore()
const signsStore = useSignsStore()
const maxFiles = MAX_SELECTABLE_FILES

const selectedFiles = ref<File[]>([])
const fileInput = ref<HTMLInputElement | null>(null)
const uploadVariantId = ref<number | null>(null)
const selectedUploadVariantValue = computed({
  get() {
    return String(uploadVariantId.value ?? '')
  },
  set(value: string) {
    uploadVariantId.value = value ? Number(value) : null
  },
})

const uploadProgress = computed(() => signsStore.uploadProgress)
const uploadPercentage = computed(() => {
  const progress = uploadProgress.value
  if (!progress || progress.totalBytes === 0) return 0
  return Math.round((progress.uploadedBytes / progress.totalBytes) * 100)
})

const showVariantSelector = computed(() => (props.variants?.length ?? 0) > 1)
const canSubmit = computed(
  () =>
    !signsStore.isUploading &&
    selectedFiles.value.length > 0 &&
    (!showVariantSelector.value || uploadVariantId.value !== null),
)

const allowedMimeTypes = new Set([
  'image/png',
  'image/jpeg',
  'image/webp',
  'image/avif',
  'video/webm',
])

// Mirrors the per-file `max:10240` (KB) rule in StoreSignRequest.
const MAX_FILE_SIZE_BYTES = 10 * 1024 * 1024

const variantOptions = computed(() => {
  if (!props.variants) return []
  return props.variants.map((v) => ({
    value: String(v.id),
    label: v.is_default ? `${v.name ?? 'Default'} (default)` : (v.name ?? 'Unnamed'),
  }))
})

function resetForm() {
  selectedFiles.value = []
  uploadVariantId.value = null
  signsStore.uploadFailedFiles = []
  signsStore.uploadCancelled = false
  if (fileInput.value) fileInput.value.value = ''
}

function validateSelectedFiles(files: File[]) {
  if (files.length === 0) return 'At least one image file is required.'
  if (files.length > maxFiles) {
    return `You may upload at most ${maxFiles} files at a time.`
  }
  const invalidFile = files.find((file) => !allowedMimeTypes.has(file.type))
  if (invalidFile) return 'Files must be PNG, JPEG, WebP, AVIF, or WebM files.'

  const oversizedFile = files.find((file) => file.size > MAX_FILE_SIZE_BYTES)
  if (oversizedFile) return `"${oversizedFile.name}" is larger than 10 MB.`

  return null
}

function close() {
  if (signsStore.isUploading) {
    signsStore.cancelUpload()
  }

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

async function runUpload(files: File[]) {
  const payload: CreateSignPayload = {
    files,
    variant_id: uploadVariantId.value ?? props.selectedVariantId ?? undefined,
  }
  const uploadedSigns = await signsStore.uploadSign(
    props.folderId,
    payload,
    authStore.signUploadMaxFiles,
  )

  if (uploadedSigns) {
    emit('saved')

    if (signsStore.uploadFailedFiles.length === 0 && !signsStore.uploadCancelled) {
      close()
    }
  }
}

async function handleSubmit() {
  signsStore.clearError()

  const fileError = validateSelectedFiles(selectedFiles.value)
  if (fileError) {
    signsStore.error = fileError
    return
  }

  if (showVariantSelector.value && uploadVariantId.value === null) {
    signsStore.error = 'Variant is required.'
    return
  }

  await runUpload(selectedFiles.value)
}

async function handleRetryFailed() {
  const filesToRetry = [...signsStore.uploadFailedFiles]
  if (filesToRetry.length === 0) return

  signsStore.clearError()
  await runUpload(filesToRetry)
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
          accept="image/png,image/jpeg,image/webp,image/avif,video/webm"
          required
          class="w-full rounded bg-surface text-sm p-2 text-on-surface file:mr-3 file:cursor-pointer file:rounded-sm file:border-0 file:bg-white file:px-3 file:py-1 file:text-xs file:font-semibold file:text-background"
          @change="handleFileChange"
        />
      </UiFormField>

      <div v-if="showVariantSelector" class="mt-3">
        <UiFormField label="Upload to variant" name="variant">
          <UiSelect
            v-model="selectedUploadVariantValue"
            name="variant"
            placeholder="Select a variant"
            :options="variantOptions"
          />
        </UiFormField>
      </div>

      <div v-if="selectedFiles.length && !signsStore.isUploading" class="mt-3">
        <p class="text-sm font-semibold text-on-surface">Selected ({{ selectedFiles.length }})</p>
        <ul
          class="mt-1 max-h-40 overflow-y-auto rounded bg-surface text-sm text-on-surface-variant"
        >
          <li
            v-for="(file, index) in selectedFiles"
            :key="`${file.name}-${index}`"
            class="truncate px-2 py-1"
          >
            {{ file.name }}
          </li>
        </ul>
      </div>

      <div v-if="signsStore.isUploading && uploadProgress" class="mt-3">
        <p class="text-sm text-on-surface-variant">
          Uploading {{ uploadProgress.completedFiles }} / {{ uploadProgress.totalFiles }} files...
        </p>
        <div class="mt-1 h-2 w-full overflow-hidden rounded bg-surface">
          <div
            class="h-full rounded bg-primary transition-all"
            :style="{ width: `${uploadPercentage}%` }"
          />
        </div>
      </div>

      <div v-if="!signsStore.isUploading && signsStore.uploadFailedFiles.length" class="mt-3">
        <p class="text-sm font-semibold text-error">
          Failed to upload ({{ signsStore.uploadFailedFiles.length }})
        </p>
        <ul
          class="mt-1 max-h-40 overflow-y-auto rounded bg-surface text-sm text-on-surface-variant"
        >
          <li
            v-for="(file, index) in signsStore.uploadFailedFiles"
            :key="`${file.name}-${index}`"
            class="truncate px-2 py-1"
          >
            {{ file.name }}
          </li>
        </ul>
      </div>

      <p class="mt-1 text-xs text-on-surface-variant">PNG, JPEG, WebP, AVIF, or WebM</p>
      <p class="mt-1 text-xs text-on-surface-variant">Up to {{ maxFiles }} files per upload.</p>

      <div class="mt-6 flex flex-wrap gap-3">
        <UiButton variant="primary" type="submit" :disabled="!canSubmit">
          {{ signsStore.isUploading ? 'Uploading...' : 'Upload' }}
        </UiButton>

        <UiButton
          v-if="!signsStore.isUploading && signsStore.uploadFailedFiles.length"
          variant="primary"
          type="button"
          @click="handleRetryFailed"
        >
          Retry failed ({{ signsStore.uploadFailedFiles.length }})
        </UiButton>

        <UiButton
          v-if="signsStore.isUploading"
          variant="secondary"
          type="button"
          @click="signsStore.cancelUpload()"
        >
          Stop upload
        </UiButton>
        <UiButton v-else variant="secondary" type="button" @click="close"> Cancel </UiButton>
      </div>
    </form>
  </UiModal>
</template>
