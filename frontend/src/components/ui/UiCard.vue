<script setup lang="ts">
withDefaults(
  defineProps<{
    maxWidth?: string
  }>(),
  { maxWidth: '40rem' },
)
</script>

<template>
  <section
    class="w-(--ui-card-width) rounded-2xl border border-border bg-surface p-8 shadow-elevated backdrop-blur-lg max-sm:p-5"
    :style="{ '--ui-card-width': `min(100%, ${maxWidth})` }"
  >
    <slot />
  </section>
</template>
