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
      'inline-flex items-center justify-center rounded-xl px-3.5 py-1.5 text-sm font-semibold no-underline transition duration-150 ease-in-out',
      fullWidth ? 'w-full' : '',
      variant === 'primary'
        ? 'border-0 bg-emerald-400 text-black disabled:cursor-wait disabled:opacity-75 hover:bg-emerald-200'
        : '',
      variant === 'danger'
        ? 'cursor-pointer border border-border-danger bg-transparent text-danger-text hover:bg-white/5'
        : '',
      variant === 'secondary'
        ? 'cursor-pointer border border-border bg-transparent text-white hover:bg-white/5'
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
