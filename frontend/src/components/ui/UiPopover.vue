<script setup lang="ts">
import { nextTick, onUnmounted, ref, watch } from 'vue'

type Placement =
  | 'bottom-start'
  | 'bottom-end'
  | 'top-start'
  | 'top-end'
  | 'left-start'
  | 'right-start'

const props = withDefaults(
  defineProps<{
    modelValue: boolean
    placement?: Placement
    offset?: number
    closeOnOutsideClick?: boolean
    closeOnEscape?: boolean
    matchTriggerWidth?: boolean
    triggerClass?: string
    panelClass?: string
  }>(),
  {
    placement: 'bottom-start',
    offset: 8,
    closeOnOutsideClick: true,
    closeOnEscape: true,
    matchTriggerWidth: false,
    triggerClass: '',
    panelClass: '',
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  closed: []
}>()

const triggerRef = ref<HTMLDivElement | null>(null)
const panelRef = ref<HTMLDivElement | null>(null)
const panelStyle = ref<Record<string, string | undefined>>({
  left: '-9999px',
  top: '-9999px',
  position: 'fixed',
  visibility: 'hidden',
})

let rafId = 0
let initialized = false

function close() {
  emit('update:modelValue', false)
}

function toggle() {
  emit('update:modelValue', !props.modelValue)
}

function clamp(value: number, min: number, max: number) {
  return Math.min(Math.max(value, min), max)
}

function setHidden() {
  panelStyle.value = {
    left: '-9999px',
    top: '-9999px',
    position: 'fixed',
    visibility: 'hidden',
  }
}

function updatePosition() {
  if (!triggerRef.value || !panelRef.value) return

  const triggerRect = triggerRef.value.getBoundingClientRect()
  const panelRect = panelRef.value.getBoundingClientRect()
  const padding = 8

  let left = triggerRect.left
  let top = triggerRect.bottom + props.offset

  switch (props.placement) {
    case 'bottom-end':
      left = triggerRect.right - panelRect.width
      break
    case 'top-start':
      top = triggerRect.top - panelRect.height - props.offset
      break
    case 'top-end':
      left = triggerRect.right - panelRect.width
      top = triggerRect.top - panelRect.height - props.offset
      break
    case 'left-start':
      left = triggerRect.left - panelRect.width - props.offset
      top = triggerRect.top
      break
    case 'right-start':
      left = triggerRect.right + props.offset
      top = triggerRect.top
      break
  }

  const maxLeft = Math.max(padding, window.innerWidth - panelRect.width - padding)
  const maxTop = Math.max(padding, window.innerHeight - panelRect.height - padding)

  panelStyle.value = {
    left: `${Math.round(clamp(left, padding, maxLeft))}px`,
    top: `${Math.round(clamp(top, padding, maxTop))}px`,
    minWidth: props.matchTriggerWidth ? `${Math.round(triggerRect.width)}px` : undefined,
    position: 'fixed',
    visibility: 'visible',
  }
}

function schedulePosition() {
  cancelAnimationFrame(rafId)
  rafId = window.requestAnimationFrame(updatePosition)
}

function onDocumentMouseDown(event: MouseEvent) {
  if (!props.modelValue || !props.closeOnOutsideClick) return

  const target = event.target as Node
  if (triggerRef.value?.contains(target) || panelRef.value?.contains(target)) return
  close()
}

function onDocumentKeydown(event: KeyboardEvent) {
  if (!props.modelValue || !props.closeOnEscape) return
  if (event.key === 'Escape') {
    close()
  }
}

function addListeners() {
  document.addEventListener('mousedown', onDocumentMouseDown)
  document.addEventListener('keydown', onDocumentKeydown)
  window.addEventListener('resize', schedulePosition)
  window.addEventListener('scroll', schedulePosition, true)
}

function removeListeners() {
  document.removeEventListener('mousedown', onDocumentMouseDown)
  document.removeEventListener('keydown', onDocumentKeydown)
  window.removeEventListener('resize', schedulePosition)
  window.removeEventListener('scroll', schedulePosition, true)
}

watch(
  () => props.modelValue,
  async (open) => {
    if (open) {
      addListeners()
      await nextTick()
      schedulePosition()
    } else {
      removeListeners()
      setHidden()
      if (initialized) {
        emit('closed')
      }
    }
    initialized = true
  },
  { immediate: true },
)

onUnmounted(() => {
  removeListeners()
  cancelAnimationFrame(rafId)
})
</script>

<template>
  <div ref="triggerRef" :class="triggerClass">
    <slot name="trigger" :open="modelValue" :toggle="toggle" :close="close" />
  </div>

  <Teleport to="body">
    <Transition name="popover">
      <div
        v-if="modelValue"
        ref="panelRef"
        :class="panelClass"
        :style="panelStyle"
      >
        <slot :close="close" />
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.popover-enter-active {
  transition:
    opacity 0.12s ease-out,
    transform 0.12s ease-out;
}

.popover-leave-active {
  transition:
    opacity 0.1s ease-in,
    transform 0.1s ease-in;
}

.popover-enter-from,
.popover-leave-to {
  opacity: 0;
  transform: translateY(-4px) scale(0.98);
}
</style>
