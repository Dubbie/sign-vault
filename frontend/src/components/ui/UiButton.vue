<script setup lang="ts">
withDefaults(
  defineProps<{
    variant?: 'primary' | 'danger' | 'secondary' | 'ghost'
    disabled?: boolean
    type?: 'button' | 'submit'
    fullWidth?: boolean
  }>(),
  { variant: 'primary', disabled: false, type: 'button', fullWidth: false },
)

const emit = defineEmits<{
  click: []
}>()
</script>

<template>
  <button
    :type="type"
    :disabled="disabled"
    :class="[
      'inline-flex cursor-pointer items-center justify-center rounded px-3.5 h-9 text-sm font-semibold no-underline transition duration-150 ease-in-out',
      fullWidth ? 'w-full' : '',
      variant === 'primary'
        ? 'border-0 bg-emerald-400 text-background disabled:cursor-default disabled:opacity-75 hover:bg-emerald-200'
        : '',
      variant === 'danger'
        ? 'cursor-pointer border border-border-danger bg-transparent text-danger-text hover:bg-white/5'
        : '',
      variant === 'secondary'
        ? 'cursor-pointer border border-border bg-transparent text-zinc-100 hover:bg-white/5'
        : '',
      variant === 'ghost'
        ? 'cursor-pointer border-0 bg-transparent text-primary underline-offset-2 hover:underline'
        : '',
    ]"
    @click="emit('click')"
  >
    <slot />
  </button>
</template>
