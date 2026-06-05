<script setup lang="ts">
defineProps<{
  title: string
  description: string
}>()
</script>

<template>
  <div class="space-y-gutter">
    <header class="space-y-3">
      <div class="max-w-3xl space-y-2">
        <h1 class="text-headline-xl text-on-surface">{{ title }}</h1>
        <p class="text-body-lg text-on-surface-variant">
          {{ description }}
        </p>
      </div>
    </header>

    <slot />
  </div>
</template>
