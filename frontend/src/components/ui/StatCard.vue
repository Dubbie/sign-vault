<script setup lang="ts">
import type { FunctionalComponent } from 'vue'

defineProps<{
  label: string
  value: string
  icon: FunctionalComponent
}>()
</script>

<template>
  <div class="glass-card p-4 rounded-lg flex flex-col justify-between h-20">
    <div class="flex justify-between items-start">
      <span class="text-on-surface-variant text-label-md">{{ label }}</span>
      <component :is="icon" class="text-primary size-4" />
    </div>

    <div class="flex items-end justify-between">
      <span class="text-headline-md leading-none cursor-text">{{ value }}</span>
    </div>
  </div>
</template>
