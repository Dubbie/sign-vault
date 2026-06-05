<script setup lang="ts">
import type { Folder } from '@/types/folder'
import { FolderIcon } from '@lucide/vue'

defineProps<{
  folder: Folder
}>()
</script>

<template>
  <RouterLink
    :to="{ name: 'folders-show', params: { id: folder.id } }"
    class="glass-card p-4 rounded-lg group cursor-pointer hover:bg-surface-container-high transition-all"
  >
    <div class="flex items-center gap-3">
      <FolderIcon class="size-6 text-primary fill-primary" />

      <div class="flex flex-col">
        <span class="text-label-md mb-1">{{ folder.name }}</span>

        <p class="flex items-center">
          <span class="text-label-sm text-on-surface-variant capitalize">{{
            folder.visibility
          }}</span>

          <template v-if="folder.variants.length > 1">
            <span class="text-label-sm text-primary mx-2 leading-none">-</span>
            <span class="text-label-sm text-on-surface-variant"
              >{{ folder.variants.length }} variants</span
            >
          </template>
        </p>
      </div>
    </div>
  </RouterLink>
</template>
