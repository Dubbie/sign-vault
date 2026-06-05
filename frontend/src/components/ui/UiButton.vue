<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'
import { RouterLink } from 'vue-router'

withDefaults(
  defineProps<{
    variant?: 'primary' | 'secondary' | 'tertiary' | 'danger' | 'link'
    size?: 'sm' | 'md' | 'lg'
    disabled?: boolean
    type?: 'button' | 'submit'
    fullWidth?: boolean
    to?: RouteLocationRaw
  }>(),
  {
    variant: 'primary',
    size: 'md',
    disabled: false,
    type: 'button',
    fullWidth: false,
  },
)

const emit = defineEmits<{
  click: [MouseEvent]
}>()

const baseClasses =
  'inline-flex cursor-pointer items-center justify-center font-semibold no-underline transition duration-150 ease-in-out disabled:opacity-50 disabled:pointer-events-none'

const sizeClasses = {
  sm: 'rounded h-8 px-3 text-sm gap-1.5',
  md: 'rounded-lg px-component-padding-x h-10 text-label-md gap-2',
  lg: 'h-11 px-4 py-3 text-body-md gap-2.5',
} as const

const variantClasses = {
  primary: 'bg-primary text-on-primary emerald-glow hover:bg-primary/90',
  secondary: 'glass-card text-on-surface hover:bg-surface-variant/50',
  tertiary: 'bg-outline-variant/20 text-on-surface hover:bg-outline-variant/40',
  link: 'text-primary hover:underline',
  danger: 'bg-error/20 text-error hover:bg-error/30',
} as const

function handleClick(event: MouseEvent) {
  emit('click', event)
}
</script>

<template>
  <RouterLink
    v-if="to"
    :to="to"
    :class="[baseClasses, sizeClasses[size], variantClasses[variant], fullWidth ? 'w-full' : '']"
    @click="handleClick"
  >
    <slot />
  </RouterLink>

  <button
    v-else
    :type="type"
    :disabled="disabled"
    :class="[baseClasses, sizeClasses[size], variantClasses[variant], fullWidth ? 'w-full' : '']"
    @click="handleClick"
  >
    <slot />
  </button>
</template>
