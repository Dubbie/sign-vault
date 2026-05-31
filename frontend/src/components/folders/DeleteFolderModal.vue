<script setup lang="ts">
import { computed } from 'vue'

import { useFoldersStore } from '@/stores/folders'

import UiModal from '@/components/ui/UiModal.vue'
import UiButton from '@/components/ui/UiButton.vue'

const props = defineProps<{
  modelValue: boolean
  folderId: number
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  deleted: []
}>()

const foldersStore = useFoldersStore()

const folder = computed(() =>
  foldersStore.folders.find((f) => f.id === props.folderId),
)

function close() {
  emit('update:modelValue', false)
}

async function handleConfirm() {
  await foldersStore.deleteFolder(props.folderId)
  emit('deleted')
  close()
}
</script>

<template>
  <UiModal :model-value="modelValue" title="Delete folder" @update:model-value="close">
    <p class="text-zinc-300">
      Are you sure you want to delete
      <span class="font-semibold text-white">{{ folder?.name ?? 'this folder' }}</span>?
      This action cannot be undone.
    </p>

    <div class="mt-6 flex flex-wrap gap-3">
      <UiButton variant="danger" type="button" @click="handleConfirm"> Delete </UiButton>
      <UiButton variant="secondary" type="button" @click="close"> Cancel </UiButton>
    </div>
  </UiModal>
</template>
