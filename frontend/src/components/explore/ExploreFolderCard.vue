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
    class="flex items-center justify-between gap-3 rounded-xl border border-white/20 bg-surface p-3 no-underline transition hover:border-emerald-400/50 hover:bg-surface-hover/50"
    :class="{ 'ring-1 ring-emerald-400 border-emerald-400/50': active }"
  >
    <div class="min-w-0 flex-1">
      <p class="truncate text-sm font-semibold text-zinc-100">{{ folder.name }}</p>
      <div v-if="folder.owner" class="mt-1 flex items-center gap-1.5">
        <img
          v-if="folder.owner.discord_avatar"
          :src="folder.owner.discord_avatar"
          :alt="folder.owner.discord_username"
          class="size-4 rounded-full"
        />
        <span class="truncate text-xs text-zinc-500">
          {{ folder.owner.discord_global_name || folder.owner.discord_username }}
        </span>
      </div>
    </div>
    <span
      class="shrink-0 rounded-full bg-emerald-400/10 px-2 py-0.5 text-xs font-semibold text-emerald-400"
    >
      {{ folder.signs_count }}
    </span>
  </RouterLink>
</template>
