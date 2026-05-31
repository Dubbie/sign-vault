<script setup lang="ts">
defineProps<{
  items: { term: string; detail: string }[]
}>()
</script>

<template>
  <dl class="grid grid-cols-[repeat(auto-fit,minmax(10rem,1fr))] gap-4">
    <div v-for="item in items" :key="item.term">
      <dt class="text-[0.8rem] uppercase tracking-[0.08em] text-text-muted">
        {{ item.term }}
      </dt>
      <dd class="mt-1 text-heading">
        {{ item.detail }}
      </dd>
    </div>
  </dl>
</template>
