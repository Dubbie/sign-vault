<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'

const props = withDefaults(
  defineProps<{
    modelValue: string
    name?: string
    options: { value: string; label: string }[]
  }>(),
  {},
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const open = ref(false)
const activeIndex = ref(-1)
const triggerRef = ref<HTMLButtonElement | null>(null)
const listboxRef = ref<HTMLUListElement | null>(null)

const selectedLabel = computed(() => {
  const opt = props.options.find((o) => o.value === props.modelValue)
  return opt ? opt.label : ''
})

const listboxId = computed(() => (props.name ? `${props.name}-listbox` : 'listbox'))
const triggerId = computed(() => (props.name ? `${props.name}-trigger` : 'trigger'))
const activeDescendantId = computed(() =>
  activeIndex.value >= 0 ? `option-${props.name ?? 'select'}-${activeIndex.value}` : undefined,
)

function selectOption(index: number) {
  const opt = props.options[index]
  if (opt) {
    emit('update:modelValue', opt.value)
    close()
  }
}

function openDropdown() {
  if (props.options.length === 0) return
  open.value = true
  const idx = props.options.findIndex((o) => o.value === props.modelValue)
  activeIndex.value = idx >= 0 ? idx : 0
  nextTick(() => {
    listboxRef.value?.focus()
    scrollToActive()
  })
}

function close() {
  open.value = false
  activeIndex.value = -1
  triggerRef.value?.focus()
}

function toggle() {
  if (open.value) {
    close()
  } else {
    openDropdown()
  }
}

function onTriggerKeydown(event: KeyboardEvent) {
  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      openDropdown()
      break
    case 'ArrowUp':
      event.preventDefault()
      openDropdown()
      activeIndex.value = props.options.length - 1
      nextTick(() => scrollToActive())
      break
    case 'Enter':
    case ' ':
      event.preventDefault()
      openDropdown()
      break
    case 'Escape':
      close()
      break
  }
}

function onListboxKeydown(event: KeyboardEvent) {
  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      activeIndex.value = Math.min(activeIndex.value + 1, props.options.length - 1)
      nextTick(() => scrollToActive())
      break
    case 'ArrowUp':
      event.preventDefault()
      activeIndex.value = Math.max(activeIndex.value - 1, 0)
      nextTick(() => scrollToActive())
      break
    case 'Enter':
    case ' ':
      event.preventDefault()
      if (activeIndex.value >= 0) {
        selectOption(activeIndex.value)
      }
      break
    case 'Escape':
      event.preventDefault()
      close()
      break
    case 'Home':
      event.preventDefault()
      activeIndex.value = 0
      nextTick(() => scrollToActive())
      break
    case 'End':
      event.preventDefault()
      activeIndex.value = props.options.length - 1
      nextTick(() => scrollToActive())
      break
  }
}

function scrollToActive() {
  const el = listboxRef.value?.children[activeIndex.value] as HTMLElement | undefined
  el?.scrollIntoView({ block: 'nearest' })
}

function onOptionHover(index: number) {
  activeIndex.value = index
}

function onOutsideClick(event: MouseEvent) {
  const target = event.target as Node
  if (
    open.value &&
    triggerRef.value &&
    listboxRef.value &&
    !triggerRef.value.contains(target) &&
    !listboxRef.value.contains(target)
  ) {
    close()
  }
}

onMounted(() => {
  document.addEventListener('mousedown', onOutsideClick)
})

onUnmounted(() => {
  document.removeEventListener('mousedown', onOutsideClick)
})
</script>

<template>
  <div class="relative">
    <button
      :id="triggerId"
      ref="triggerRef"
      type="button"
      role="combobox"
      :aria-haspopup="'listbox'"
      :aria-expanded="open"
      :aria-controls="listboxId"
      :aria-activedescendant="activeDescendantId"
      :aria-label="name"
      class="flex w-full h-9 items-center justify-between rounded-md bg-surface px-3 text-sm text-zinc-100 transition hover:bg-surface-hover focus:bg-surface-focus focus:outline-hidden"
      @click="toggle"
      @keydown="onTriggerKeydown"
    >
      <span>{{ selectedLabel }}</span>
      <svg
        class="size-4 text-zinc-400 transition duration-200"
        :class="open ? 'rotate-180' : ''"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <Transition name="dropdown">
      <ul
        v-if="open"
        :id="listboxId"
        ref="listboxRef"
        role="listbox"
        tabindex="-1"
        :aria-label="name"
        class="absolute z-50 mt-1 w-full rounded-md bg-surface py-1 shadow-lg ring-1 ring-white/10 focus:outline-hidden"
        @keydown="onListboxKeydown"
      >
        <li
          v-for="(opt, idx) in options"
          :key="opt.value"
          :id="`option-${name ?? 'select'}-${idx}`"
          role="option"
          :aria-selected="opt.value === modelValue"
          :class="[
            'cursor-pointer px-3 py-1.5 text-sm transition',
            opt.value === modelValue
              ? 'text-zinc-100'
              : 'text-zinc-400',
            idx === activeIndex ? 'bg-white/10' : '',
          ]"
          @click="selectOption(idx)"
          @mouseenter="onOptionHover(idx)"
        >
          {{ opt.label }}
        </li>
      </ul>
    </Transition>
  </div>
</template>

<style scoped>
.dropdown-enter-active {
  transition:
    opacity 0.15s ease-out,
    transform 0.15s ease-out;
}

.dropdown-leave-active {
  transition:
    opacity 0.1s ease-in,
    transform 0.1s ease-in;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
