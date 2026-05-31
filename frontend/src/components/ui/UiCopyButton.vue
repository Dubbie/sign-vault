<script setup lang="ts">
withDefaults(
  defineProps<{
    copied: boolean
    minWidth?: string
  }>(),
  { minWidth: '6.5rem' },
)

const emit = defineEmits<{
  copy: []
}>()
</script>

<template>
  <button
    type="button"
    :style="{ minWidth }"
    class="cursor-pointer rounded-xl border border-border bg-transparent px-[0.9rem] py-[0.6rem] text-heading transition duration-150 ease-in-out hover:bg-white/5"
    @click="emit('copy')"
  >
    {{ copied ? 'Copied!' : 'Copy URL' }}
  </button>
</template>
