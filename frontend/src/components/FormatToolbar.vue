<script setup lang="ts">
import { ref, computed, watch, nextTick, onUnmounted } from 'vue'
import type { SelectedFormat } from '@/composables/useNameTagFormatter'

interface ToolbarPosition {
  x: number
  y: number
}

type FormatMode = 'solid' | 'gradient'

const props = withDefaults(
  defineProps<{
    visible: boolean
    position: ToolbarPosition
    format?: SelectedFormat
    editorRef?: HTMLElement | null
  }>(),
  { format: () => ({ type: 'none' }) as SelectedFormat },
)

const toolbarRef = ref<HTMLElement | null>(null)
const clampedX = ref(0)
let resizeObserver: ResizeObserver | null = null

function reclamp() {
  if (!toolbarRef.value) return
  const w = toolbarRef.value.offsetWidth
  const editorEl = props.editorRef
  if (!editorEl) {
    clampedX.value = Math.round(props.position.x - w / 2)
    return
  }
  const editorRect = editorEl.getBoundingClientRect()
  const wantLeft = props.position.x - w / 2
  clampedX.value = Math.round(Math.min(Math.max(wantLeft, editorRect.left), editorRect.right - w))
}

watch(
  () => props.visible,
  (visible) => {
    if (visible) {
      clampedX.value = props.position.x
      nextTick(() => {
        if (toolbarRef.value) {
          resizeObserver?.disconnect()
          resizeObserver = new ResizeObserver(reclamp)
          resizeObserver.observe(toolbarRef.value)
          reclamp()
        }
      })
    } else {
      resizeObserver?.disconnect()
      resizeObserver = null
    }
  },
)

onUnmounted(() => {
  resizeObserver?.disconnect()
})

const emit = defineEmits<{
  'apply-solid': [color: string]
  'apply-gradient': [from: string, to: string]
  reset: []
}>()

const mode = ref<FormatMode>('solid')
const colorPicker = ref('#ffffff')
const gradientFromColor = ref('#38bdf8')
const gradientToColor = ref('#6366f1')

const gradientPreviewStyle = computed(() => ({
  background: `linear-gradient(to right, ${gradientFromColor.value}, ${gradientToColor.value})`,
}))

watch(
  () => props.format,
  (format) => {
    if (format.type === 'solid') {
      mode.value = 'solid'
      colorPicker.value = format.color
    } else if (format.type === 'gradient') {
      mode.value = 'gradient'
      gradientFromColor.value = format.fromColor
      gradientToColor.value = format.toColor
    } else {
      mode.value = 'solid'
      colorPicker.value = '#ffffff'
    }
  },
)

function onSolidColorChange(event: Event) {
  const value = (event.target as HTMLInputElement).value
  colorPicker.value = value
  emit('apply-solid', value)
}

function onApplyGradient() {
  emit('apply-gradient', gradientFromColor.value, gradientToColor.value)
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="translate-y-1 opacity-0"
      leave-active-class="transition duration-100 ease-in"
      leave-to-class="translate-y-1 opacity-0"
    >
      <div
        v-if="visible"
        ref="toolbarRef"
        :style="{
          left: `${clampedX}px`,
          top: `${position.y}px`,
        }"
        data-format-toolbar
        class="fixed -translate-y-full rounded-md border border-outline-variant bg-surface px-2 py-1.5 shadow-elevated flex items-center gap-1.5 z-50"
      >
        <div class="flex rounded border border-outline-variant p-0.5">
          <button
            class="cursor-pointer rounded-xs px-2 py-0.5 text-xs font-medium transition-colors"
            :class="
              mode === 'solid' ? 'bg-white/10 text-zinc-100' : 'text-zinc-500 hover:text-zinc-300'
            "
            @click="mode = 'solid'"
          >
            Solid
          </button>
          <button
            class="cursor-pointer rounded-xs px-2 py-0.5 text-xs font-medium transition-colors"
            :class="
              mode === 'gradient'
                ? 'bg-white/10 text-zinc-100'
                : 'text-zinc-500 hover:text-zinc-300'
            "
            @click="mode = 'gradient'"
          >
            Gradient
          </button>
        </div>

        <div class="h-6 w-px bg-border" />

        <template v-if="mode === 'solid'">
          <button
            class="relative size-8 cursor-pointer overflow-hidden rounded-md border border-outline-variant transition-transform hover:scale-110"
            title="Solid color"
            @click="($refs.solidInput as HTMLInputElement)?.click()"
          >
            <span class="absolute inset-0 rounded-md" :style="{ backgroundColor: colorPicker }" />
            <input
              ref="solidInput"
              type="color"
              :value="colorPicker"
              class="absolute inset-0 cursor-pointer opacity-0"
              @input="onSolidColorChange"
            />
          </button>
        </template>

        <template v-if="mode === 'gradient'">
          <div class="flex items-center gap-1">
            <button
              class="relative size-7 cursor-pointer overflow-hidden rounded-lg border border-outline-variant transition-transform hover:scale-110"
              title="Gradient start color"
              @click="($refs.gradientFromInput as HTMLInputElement)?.click()"
            >
              <span
                class="absolute inset-0 rounded"
                :style="{ backgroundColor: gradientFromColor }"
              />
              <input
                ref="gradientFromInput"
                type="color"
                v-model="gradientFromColor"
                class="absolute inset-0 cursor-pointer opacity-0"
              />
            </button>

            <div
              class="h-4 w-8 rounded border border-outline-variant"
              :style="gradientPreviewStyle"
              title="Gradient preview"
            />

            <button
              class="relative size-7 cursor-pointer overflow-hidden rounded-lg border border-outline-variant transition-transform hover:scale-110"
              title="Gradient end color"
              @click="($refs.gradientToInput as HTMLInputElement)?.click()"
            >
              <span
                class="absolute inset-0 rounded-md"
                :style="{ backgroundColor: gradientToColor }"
              />
              <input
                ref="gradientToInput"
                type="color"
                v-model="gradientToColor"
                class="absolute inset-0 cursor-pointer opacity-0"
              />
            </button>

            <button
              class="flex h-7 cursor-pointer items-center rounded border border-outline-variant px-2 text-xs font-medium text-zinc-300 transition-colors hover:bg-white/5 hover:text-zinc-100"
              title="Apply gradient"
              @click="onApplyGradient"
            >
              Apply
            </button>
          </div>
        </template>

        <div class="h-6 w-px bg-border" />

        <button
          class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-md text-zinc-500 transition-colors hover:bg-white/5 hover:text-zinc-300"
          title="Reset formatting"
          @click="emit('reset')"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 20 20"
            fill="currentColor"
            class="h-4 w-4"
          >
            <path
              fill-rule="evenodd"
              d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.433a.75.75 0 0 0 0-1.5H3.989a.75.75 0 0 0-.75.75v4.242a.75.75 0 0 0 1.5 0v-2.43l.31.31a7 7 0 0 0 11.712-3.138.75.75 0 0 0-1.449-.39Zm1.23-3.723a.75.75 0 0 0 .219-.53V2.929a.75.75 0 0 0-1.5 0V5.36l-.31-.31A7 7 0 0 0 3.239 8.188a.75.75 0 1 0 1.448.389A5.5 5.5 0 0 1 13.89 6.11l.311.31h-2.432a.75.75 0 0 0 0 1.5h4.243a.75.75 0 0 0 .53-.219Z"
              clip-rule="evenodd"
            />
          </svg>
        </button>

        <slot name="toolbar-actions" />
      </div>
    </Transition>
  </Teleport>
</template>
