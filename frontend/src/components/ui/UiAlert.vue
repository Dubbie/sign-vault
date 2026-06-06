<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    tone?: 'info' | 'success' | 'warning' | 'danger'
  }>(),
  {
    tone: 'info',
  },
)

const toneClasses = computed(() => {
  switch (props.tone) {
    case 'success':
      return 'border-emerald-400/20 bg-emerald-400/5 text-emerald-100'
    case 'warning':
      return 'border-amber-400/20 bg-amber-400/5 text-amber-50'
    case 'danger':
      return 'border-red-400/20 bg-red-400/5 text-red-100'
    default:
      return 'border-white/10 bg-white/[0.03] text-zinc-200'
  }
})

const iconClasses = computed(() => {
  switch (props.tone) {
    case 'success':
      return 'text-primary'
    case 'warning':
      return 'text-amber-300'
    case 'danger':
      return 'text-error'
    default:
      return 'text-primary'
  }
})

const iconPath = computed(() => {
  switch (props.tone) {
    case 'success':
      return 'M9 12.75 11.25 15 15 9.75'
    case 'warning':
      return 'M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z'
    case 'danger':
      return 'M12 9v3.75m0 4.5h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z'
    default:
      return 'M12 16.5v-4m0-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'
  }
})

const iconStrokeWidth = computed(() =>
  props.tone === 'warning' || props.tone === 'danger' ? 1.75 : 2,
)
</script>

<template>
  <div class="glass-card rounded-lg p-6 relative overflow-hidden group" :class="toneClasses">
    <div
      class="absolute inset-0 bg-gradient-to-r from-primary/5 to-transparent opacity-0 transition-opacity pointer-events-none group-hover:opacity-100"
    ></div>
    <div class="flex flex-col md:flex-row items-center gap-6">
      <div
        class="size-12 rounded-lg bg-surface-container-high flex items-center justify-center border border-outline-variant/30"
      >
        <svg
          class="size-6"
          :class="iconClasses"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            :stroke-width="iconStrokeWidth"
            :d="iconPath"
          />
        </svg>
      </div>
      <div class="min-w-0 flex-1 text-sm leading-6">
        <slot />
      </div>
      <div v-if="$slots.actions" class="shrink-0 self-center">
        <slot name="actions" />
      </div>
    </div>
  </div>
</template>
