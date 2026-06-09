<script setup lang="ts">
import { computed } from 'vue'

import type { EditableFolderAuthor } from '@/lib/folder-authors'

import UiButton from '@/components/ui/UiButton.vue'
import UiFormField from '@/components/ui/UiFormField.vue'
import UiInput from '@/components/ui/UiInput.vue'
import { Plus, Trash2 } from '@lucide/vue'

const model = defineModel<EditableFolderAuthor[]>({ required: true })

const canRemoveAuthors = computed(() => model.value.length > 1)

function addAuthor() {
  model.value = [
    ...model.value,
    {
      name: '',
      source_url: '',
    },
  ]
}

function updateAuthor(index: number, patch: Partial<EditableFolderAuthor>) {
  model.value = model.value.map((author, currentIndex) =>
    currentIndex === index ? { ...author, ...patch } : author,
  )
}

function removeAuthor(index: number) {
  if (model.value.length === 1) {
    updateAuthor(index, { name: '', source_url: '' })
    return
  }

  model.value = model.value.filter((_, currentIndex) => currentIndex !== index)
}
</script>

<template>
  <div class="grid gap-4">
    <div
      v-for="(author, index) in model"
      :key="index"
      class="rounded-xl border border-white/10 bg-white/[0.03] p-4"
    >
      <div class="mb-3 flex items-center justify-between gap-3">
        <p class="text-sm font-semibold text-zinc-100">Author {{ index + 1 }}</p>

        <UiButton
          variant="link"
          type="button"
          size="sm"
          :disabled="!canRemoveAuthors && !author.name && !author.source_url"
          @click="removeAuthor(index)"
        >
          <Trash2 class="size-4" />
          Remove
        </UiButton>
      </div>

      <div class="grid gap-4">
        <UiFormField
          :label="index === 0 ? 'Author name' : `Author ${index + 1} name`"
          :name="`authors.${index}.name`"
        >
          <UiInput
            :model-value="author.name"
            type="text"
            :name="`authors.${index}.name`"
            placeholder="e.g. Buried, xXTrackMakerXx"
            @update:model-value="updateAuthor(index, { name: $event })"
          />
        </UiFormField>

        <UiFormField
          :label="index === 0 ? 'Source URL' : `Author ${index + 1} source URL`"
          :name="`authors.${index}.source_url`"
        >
          <UiInput
            :model-value="author.source_url"
            type="url"
            :name="`authors.${index}.source_url`"
            placeholder="https://..."
            @update:model-value="updateAuthor(index, { source_url: $event })"
          />
        </UiFormField>
      </div>
    </div>

    <div>
      <UiButton variant="secondary" type="button" @click="addAuthor">
        <Plus class="size-4" />
        Add author
      </UiButton>
    </div>
  </div>
</template>
