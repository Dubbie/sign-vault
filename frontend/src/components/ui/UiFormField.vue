<script setup lang="ts">
defineProps<{
  label: string
  name?: string
}>()
</script>

<template>
  <label class="grid gap-[0.4rem]">
    <span class="text-[0.9rem] text-text-muted">{{ label }}</span>
    <slot />
  </label>
</template>
