<script setup lang="ts">
import { RouterLink } from 'vue-router'

import type { PublicFolderListing } from '@/types/public-folder'

defineProps<{
  folder: PublicFolderListing
  active?: boolean
}>()
</script>

<template>
  <RouterLink
    :to="{ name: 'public-folder', params: { slug: folder.slug } }"
    class="flex items-center justify-between gap-3 rounded-lg border bg-surface p-3 no-underline transition hover:bg-surface-hover/50"
    :class="{ 'border-primary': active, 'border-outline/30': !active }"
  >
    <div class="min-w-0 flex-1">
      <p class="text-label-md mb-2 truncate">{{ folder.name }}</p>
      <div v-if="folder.owner" class="mt-1 flex items-center gap-1.5">
        <img
          v-if="folder.owner.avatar_url"
          :src="folder.owner.avatar_url"
          :alt="folder.owner.display_name"
          class="size-4 rounded"
        />
        <span class="truncate text-xs text-secondary">
          {{ folder.owner.display_name }}
        </span>
      </div>
    </div>
    <div class="flex flex-col h-full justify-between items-end">
      <span
        class="shrink-0 text-xs px-1.5 py-0.5 rounded bg-primary/10 border border-primary/20 text-primary"
      >
        {{ folder.signs_count }} signs
      </span>
      <span
        v-if="folder.variants_count > 1"
        class="shrink-0 px-1.5 text-xs text-on-surface-variant"
      >
        {{ folder.variants_count }} variants
      </span>
    </div>
  </RouterLink>
</template>
