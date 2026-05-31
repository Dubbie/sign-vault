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
      'inline-flex items-center justify-center rounded-xl px-4 py-[0.85rem] font-bold no-underline transition duration-150 ease-in-out',
      fullWidth ? 'w-full' : '',
      variant === 'primary'
        ? 'border-0 bg-gradient-to-br from-primary to-primary-strong text-sky-950 hover:translate-y-[-1px] disabled:cursor-wait disabled:opacity-75'
        : '',
      variant === 'danger'
        ? 'cursor-pointer border border-border-danger bg-transparent text-danger-text hover:bg-white/5'
        : '',
      variant === 'secondary'
        ? 'cursor-pointer border border-border bg-transparent text-heading hover:bg-white/5'
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
