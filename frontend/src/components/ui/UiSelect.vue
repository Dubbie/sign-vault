<script setup lang="ts">
import { ChevronDown } from '@lucide/vue'
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'

const props = withDefaults(
  defineProps<{
    modelValue: string
    name?: string
    placeholder?: string
    options: { value: string; label: string; disabled?: boolean }[]
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
  if (opt) return opt.label
  return props.placeholder ?? ''
})

const listboxId = computed(() => (props.name ? `${props.name}-listbox` : 'listbox'))
const triggerId = computed(() => (props.name ? `${props.name}-trigger` : 'trigger'))
const activeDescendantId = computed(() =>
  activeIndex.value >= 0 ? `option-${props.name ?? 'select'}-${activeIndex.value}` : undefined,
)

function selectOption(index: number) {
  const opt = props.options[index]
  if (opt && !opt.disabled) {
    emit('update:modelValue', opt.value)
    close()
  }
}

function nextEnabledIndex(startIndex: number, direction: 1 | -1) {
  if (props.options.length === 0) return -1

  let index = startIndex
  while (index >= 0 && index < props.options.length) {
    if (!props.options[index]?.disabled) return index
    index += direction
  }

  return -1
}

function openDropdown() {
  if (props.options.length === 0) return
  open.value = true
  const selectedIndex = props.options.findIndex((o) => o.value === props.modelValue && !o.disabled)
  activeIndex.value = selectedIndex >= 0 ? selectedIndex : nextEnabledIndex(0, 1)
  nextTick(() => {
    listboxRef.value?.focus()
    scrollToActive()
  })
}

function close(focusTrigger = true) {
  open.value = false
  activeIndex.value = -1

  if (focusTrigger) {
    triggerRef.value?.focus()
  }
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
      activeIndex.value = nextEnabledIndex(
        Math.min(activeIndex.value + 1, props.options.length - 1),
        1,
      )
      nextTick(() => scrollToActive())
      break
    case 'ArrowUp':
      event.preventDefault()
      activeIndex.value = nextEnabledIndex(Math.max(activeIndex.value - 1, 0), -1)
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
      activeIndex.value = nextEnabledIndex(0, 1)
      nextTick(() => scrollToActive())
      break
    case 'End':
      event.preventDefault()
      activeIndex.value = nextEnabledIndex(props.options.length - 1, -1)
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
      class="flex w-full items-center justify-between bg-surface-container-low border border-outline-variant/30 rounded-lg px-component-padding-x h-10 focus:outline-none focus:border-primary transition-all"
      @click="toggle"
      @keydown="onTriggerKeydown"
    >
      <span>{{ selectedLabel }}</span>

      <ChevronDown
        class="size-4 text-on-surface-variant transition duration-200"
        :class="open ? 'rotate-180' : ''"
      />
    </button>

    <ul
      ref="listboxRef"
      role="listbox"
      tabindex="-1"
      :id="listboxId"
      :aria-label="name"
      :class="[
        'absolute z-50 mt-1 w-full rounded-md bg-surface-container py-1 shadow-lg ring-1 ring-outline-variant/30 focus:outline-hidden',
        open ? 'visible opacity-100' : 'invisible opacity-0 pointer-events-none',
      ]"
      @keydown="onListboxKeydown"
    >
      <li
        v-for="(opt, idx) in options"
        :key="opt.value"
        :id="`option-${name ?? 'select'}-${idx}`"
        role="option"
        :aria-selected="opt.value === modelValue"
        :aria-disabled="opt.disabled ? 'true' : 'false'"
        :class="[
          'px-3 py-1.5 text-sm transition',
          opt.disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer',
          opt.value === modelValue ? 'text-primary' : 'text-on-surface-variant',
          idx === activeIndex && !opt.disabled ? 'bg-white/10' : '',
        ]"
        @mousedown.prevent="selectOption(idx)"
        @mouseenter="!opt.disabled && onOptionHover(idx)"
      >
        {{ opt.label }}
      </li>
    </ul>
  </div>
</template>
