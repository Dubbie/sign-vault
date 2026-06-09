<script setup lang="ts">
import { computed } from 'vue'

import type { FolderAuthor } from '@/types/folder'

const props = withDefaults(
  defineProps<{
    authors: FolderAuthor[]
    prefix?: string
    compact?: boolean
  }>(),
  {
    prefix: 'Original author:',
    compact: false,
  },
)

const visibleAuthors = computed(() => props.authors.filter((author) => author.name.trim() !== ''))
</script>

<template>
  <span v-if="visibleAuthors.length" class="inline">
    <span>{{ prefix }}</span>
    <span class="ml-1">
      <template v-for="(author, index) in visibleAuthors" :key="`${author.name}-${index}`">
        <a
          v-if="author.source_url"
          :href="author.source_url"
          target="_blank"
          rel="noopener noreferrer"
          class="text-primary hover:text-primary/80"
          @click.stop
        >
          {{ author.name }}
        </a>
        <span v-else class="text-on-surface">{{ author.name }}</span>
        <span v-if="index < visibleAuthors.length - 1">{{ compact ? ', ' : ', ' }}</span>
      </template>
    </span>
  </span>
</template>
