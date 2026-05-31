<script setup lang="ts">
defineProps<{
  label: string
}>()
</script>

<template>
  <span
    class="rounded-full border border-border px-[0.65rem] py-[0.3rem] text-[0.85rem] capitalize text-heading"
  >
    {{ label }}
  </span>
</template>
