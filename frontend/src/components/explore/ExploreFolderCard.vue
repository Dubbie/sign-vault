<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import { voteFolder } from '@/lib/public-folders'
import { useAuthStore } from '@/stores/auth'
import type { PublicFolderListing } from '@/types/public-folder'
import FolderAuthorsInline from '@/components/folders/FolderAuthorsInline.vue'
import UiAvatar from '@/components/ui/UiAvatar.vue'
import { ThumbsUp } from '@lucide/vue'

const props = defineProps<{
  folder: PublicFolderListing
  active?: boolean
  id?: string
  tabindex?: number
}>()

const auth = useAuthStore()
const router = useRouter()

const votesCount = ref(props.folder.votes_count)
const userHasVoted = ref(props.folder.user_has_voted)
const isVoting = ref(false)
const showVoteButton = computed(() => auth.user || votesCount.value > 0)

async function handleVote(e: MouseEvent) {
  e.preventDefault()
  e.stopPropagation()

  if (!auth.user) {
    await router.push({ name: 'login' })
    return
  }

  if (isVoting.value) return

  isVoting.value = true
  try {
    const result = await voteFolder(props.folder.slug)
    votesCount.value = result.votes_count
    userHasVoted.value = result.user_has_voted
  } finally {
    isVoting.value = false
  }
}
</script>

<template>
  <RouterLink
    :id="id"
    :to="{ name: 'public-folder', params: { slug: folder.slug } }"
    role="option"
    :aria-selected="active ? 'true' : 'false'"
    :tabindex="tabindex ?? -1"
    class="flex flex-col gap-2 rounded-lg border bg-surface px-3 py-2.5 no-underline transition hover:bg-surface-hover/50"
    :class="{ 'border-primary': active, 'border-outline/30': !active }"
  >
    <div class="flex items-center gap-1.5 min-w-0">
      <p class="min-w-0 truncate py-0.5 text-label-md leading-[1.2]">{{ folder.name }}</p>
      <span v-if="folder.authors.length" class="shrink-0 text-xs text-secondary">
        <FolderAuthorsInline :authors="folder.authors" prefix="by" compact />
      </span>
    </div>

    <div class="flex items-center gap-2 min-w-0">
      <div v-if="folder.owner" class="flex items-center gap-2 min-w-0 flex-1">
        <UiAvatar
          :name="folder.owner.display_name"
          :src="folder.owner.avatar_url"
          class="size-5 rounded"
        />
        <span class="truncate text-xs text-secondary">
          {{ folder.owner.display_name }}
        </span>
      </div>

      <span class="shrink-0 text-xs text-on-surface-variant"> {{ folder.signs_count }} signs </span>

      <span v-if="folder.variants_count > 1">·</span>

      <span v-if="folder.variants_count > 1" class="shrink-0 text-xs text-on-surface-variant">
        {{ folder.variants_count }} variants
      </span>

      <button
        v-if="showVoteButton"
        type="button"
        class="shrink-0 ml-auto flex h-6 cursor-pointer items-center gap-1.5 rounded px-2 text-xs font-bold transition-colors"
        :class="
          userHasVoted
            ? 'bg-emerald-500 text-background'
            : 'bg-zinc-700 text-zinc-300 hover:bg-emerald-500 hover:text-white'
        "
        :disabled="isVoting"
        :title="auth.user ? (userHasVoted ? 'Remove vote' : 'Vote ++') : 'Login to vote'"
        @click="handleVote"
      >
        <ThumbsUp class="size-4" />
        <span v-if="votesCount > 0">{{ votesCount }}</span>
      </button>
    </div>
  </RouterLink>
</template>
