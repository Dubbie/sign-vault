<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    label: string
    size?: 'sm' | 'md' | 'lg'
  }>(),
  {
    size: 'md',
  },
)
</script>

<template>
  <span
    :class="[
      'rounded-full bg-white capitalize text-background font-semibold',
      props.size === 'sm' ? 'px-2 py-0.5 text-[11px]' : '',
      props.size === 'md' ? 'px-2.5 py-1 text-xs' : '',
      props.size === 'lg' ? 'px-3 py-1.5 text-sm' : '',
    ]"
  >
    {{ label }}
  </span>
</template>
