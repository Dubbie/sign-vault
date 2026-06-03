<script setup lang="ts">
import { computed, ref, watch } from 'vue'

import { useFoldersStore } from '@/stores/folders'
import { useSignsStore } from '@/stores/signs'

import UiModal from '@/components/ui/UiModal.vue'
import UiErrorBanner from '@/components/ui/UiErrorBanner.vue'
import UiFormField from '@/components/ui/UiFormField.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiButton from '@/components/ui/UiButton.vue'

const props = defineProps<{
  modelValue: boolean
  folderId: number
  signIds: number[]
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  saved: []
}>()

const foldersStore = useFoldersStore()
const signsStore = useSignsStore()

const selectedFolderId = ref('')

const availableFolders = computed(() =>
  foldersStore.folders
    .filter((folder) => folder.id !== props.folderId)
    .map((folder) => ({
      value: String(folder.id),
      label: folder.name,
    })),
)

const canSubmit = computed(
  () => selectedFolderId.value !== '' && !signsStore.isMoving,
)

function close() {
  emit('update:modelValue', false)
  selectedFolderId.value = ''
  signsStore.clearError()
}

async function handleSubmit() {
  if (!canSubmit.value) return

  const moved = await signsStore.moveSigns(props.signIds, Number(selectedFolderId.value))

  if (moved) {
    emit('saved')
    close()
  }
}

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      foldersStore.fetchFolders()
    }
  },
)
</script>

<template>
  <UiModal :model-value="modelValue" title="Move signs" @update:model-value="close">
    <UiErrorBanner v-if="signsStore.error">
      {{ signsStore.error }}
    </UiErrorBanner>

    <p class="text-sm text-zinc-300 mb-4">
      Moving
      <span class="font-semibold text-zinc-100">{{ props.signIds.length }}</span>
      sign{{ props.signIds.length === 1 ? '' : 's' }} to another folder.
    </p>

    <form @submit.prevent="handleSubmit">
      <UiFormField label="Target folder" name="target_folder">
        <UiSelect
          v-model="selectedFolderId"
          name="target_folder"
          :options="availableFolders"
        />
      </UiFormField>

      <p v-if="availableFolders.length === 0 && !foldersStore.isLoading" class="mt-2 text-xs text-zinc-400">
        No other folders available.
      </p>

      <div class="mt-6 flex flex-wrap gap-3">
        <UiButton variant="primary" type="submit" :disabled="!canSubmit">
          {{ signsStore.isMoving ? 'Moving...' : 'Move' }}
        </UiButton>
        <UiButton variant="secondary" type="button" @click="close">
          Cancel
        </UiButton>
      </div>
    </form>
  </UiModal>
</template>
