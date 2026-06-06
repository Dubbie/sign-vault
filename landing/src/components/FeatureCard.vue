<script setup lang="ts">
defineProps<{
  title: string;
  description: string;
}>();
</script>

<template>
  <div
    class="rounded-lg border border-border bg-surface p-6 backdrop-blur transition hover:border-zinc-600"
  >
    <div
      class="mb-3 flex size-10 items-center justify-center rounded-lg bg-emerald-400/10"
    >
      <slot name="icon" />
    </div>
    <h3 class="mb-2 text-lg font-semibold text-heading">{{ title }}</h3>
    <p class="text-sm leading-relaxed text-muted">{{ description }}</p>
  </div>
</template>
