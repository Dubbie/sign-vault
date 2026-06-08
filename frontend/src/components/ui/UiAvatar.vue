<script setup lang="ts">
import { computed, ref, watch } from 'vue'

const props = withDefaults(
  defineProps<{
    name?: string | null
    src?: string | null
    alt?: string
    textClass?: string
  }>(),
  {
    name: '',
    src: null,
    alt: undefined,
    textClass: 'text-xs',
  },
)

const avatarLoadFailed = ref(false)

watch(
  () => props.src,
  () => {
    avatarLoadFailed.value = false
  },
)

const fallbackPalette = [
  'bg-emerald-500 text-white',
  'bg-sky-500 text-white',
  'bg-amber-500 text-background',
  'bg-rose-500 text-white',
  'bg-cyan-600 text-white',
  'bg-indigo-500 text-white',
  'bg-teal-500 text-background',
  'bg-fuchsia-500 text-white',
] as const

const trimmedName = computed(() => props.name?.trim() ?? '')
const initials = computed(() => {
  if (!trimmedName.value) {
    return '?'
  }

  const segments = trimmedName.value.split(/\s+/).filter(Boolean)

  if (segments.length === 1) {
    return (segments[0] ?? '?').slice(0, 2).toUpperCase()
  }

  return segments
    .slice(0, 2)
    .map((segment) => segment[0] ?? '')
    .join('')
    .toUpperCase()
})

const fallbackToneClass = computed(() => {
  const seed = trimmedName.value || '?'
  let hash = 0

  for (const character of seed) {
    hash = (hash * 31 + character.charCodeAt(0)) >>> 0
  }

  return fallbackPalette[hash % fallbackPalette.length]
})

const imageAlt = computed(() => props.alt ?? (trimmedName.value || 'User avatar'))
const showImage = computed(() => Boolean(props.src) && !avatarLoadFailed.value)

function handleImageError() {
  avatarLoadFailed.value = true
}
</script>

<template>
  <div
    class="inline-flex shrink-0 items-center justify-center overflow-hidden font-semibold select-none"
    :class="showImage ? 'bg-surface-container-high' : fallbackToneClass"
  >
    <img
      v-if="showImage"
      :src="src ?? undefined"
      :alt="imageAlt"
      class="h-full w-full object-cover"
      @error="handleImageError"
    />
    <span v-else :class="textClass">{{ initials }}</span>
  </div>
</template>
