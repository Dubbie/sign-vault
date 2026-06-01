<script setup lang="ts">
defineProps<{
  label: string
}>()
</script>

<template>
  <span class="rounded-full bg-white px-2.5 py-1 text-xs capitalize text-background font-semibold">
    {{ label }}
  </span>
</template>
