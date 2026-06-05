<script setup lang="ts">
defineProps<{
  title: string
  description?: string
}>()
</script>

<template>
  <section>
    <div class="space-y-2">
      <h2 class="text-headline-md text-on-surface">{{ title }}</h2>
      <p v-if="description" class="max-w-3xl text-body-md text-on-surface-variant">
        {{ description }}
      </p>
    </div>

    <div class="mt-6">
      <slot />
    </div>
  </section>
</template>
