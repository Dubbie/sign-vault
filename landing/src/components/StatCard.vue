<script setup lang="ts">
defineProps<{
  label: string;
  value: string;
  suffix?: string;
  muted?: boolean;
}>();
</script>

<template>
  <div class="text-center">
    <p class="mt-1 text-xs uppercase font-mono text-emerald-400">
      {{ label }}
    </p>
    <p
      :class="
        muted
          ? 'text-sm text-muted'
          : 'text-3xl font-bold text-heading sm:text-4xl'
      "
    >
      <template v-if="suffix">
        {{ value }}<span class="text-sm text-muted">{{ suffix }}</span>
      </template>
      <template v-else>
        {{ value }}
      </template>
    </p>
  </div>
</template>
