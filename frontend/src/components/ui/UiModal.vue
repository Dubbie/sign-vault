<script setup lang="ts">
import { X } from '@lucide/vue'

defineProps<{
  modelValue: boolean
  title: string
  size?: 'md' | 'lg' | 'xl'
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  closed: []
}>()

function close() {
  emit('update:modelValue', false)
}

function handleAfterLeave() {
  emit('closed')
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal" @after-leave="handleAfterLeave">
      <div
        v-if="modelValue"
        class="fixed inset-0 z-50 flex items-center justify-center bg-background/80 backdrop-blur px-4"
        @click.self="close"
      >
        <div
          class="modal-box glass-card w-full rounded-xl p-6 shadow-2xl"
          :class="{
            'max-w-lg': size === 'md' || !size,
            'max-w-2xl': size === 'lg',
            'max-w-4xl': size === 'xl',
          }"
        >
          <div class="mb-6 flex items-center justify-between">
            <h2 class="text-headline-md">{{ title }}</h2>
            <button
              type="button"
              class="cursor-pointer text-on-surface-variant hover:text-primary transition-all"
              @click="close"
            >
              <X class="size-5" />
            </button>
          </div>
          <slot />
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active {
  transition: opacity 0.2s ease-out;
}

.modal-leave-active {
  transition: opacity 0.15s ease-in;
}

.modal-enter-active .modal-box {
  transition:
    opacity 0.2s ease-out,
    transform 0.2s ease-out;
}

.modal-leave-active .modal-box {
  transition:
    opacity 0.15s ease-in,
    transform 0.15s ease-in;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-box,
.modal-leave-to .modal-box {
  opacity: 0;
  transform: scale(0.95);
}
</style>
