<script setup lang="ts">
defineProps<{
  modelValue: boolean
  title: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

function close() {
  emit('update:modelValue', false)
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="modelValue"
        class="fixed inset-0 z-50 flex items-center justify-center bg-background/80 backdrop-blur px-4"
        @click.self="close"
      >
        <div
          class="modal-box ring-2 ring-white/20 w-full max-w-lg rounded-md bg-background p-6 shadow-2xl"
        >
          <div class="mb-6 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-zinc-100">{{ title }}</h2>
            <button
              type="button"
              class="cursor-pointer text-zinc-400 transition hover:text-zinc-100"
              @click="close"
            >
              <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
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
