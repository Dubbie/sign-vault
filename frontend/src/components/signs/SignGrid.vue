<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { getGridBackgroundSurfaceClasses } from '@/lib/grid-background-presets'
import SignMedia from '@/components/signs/SignMedia.vue'
import type { GridBackgroundPreset } from '@/types/grid-background'

interface GridSign {
  id: number
  name: string
  public_url: string
  thumbnail_url: string | null
  mime_type: string
  width: number | null
  height: number | null
  column_ratio: number | null
}

const props = withDefaults(
  defineProps<{
    signs: GridSign[]
    copiedSignId: number | null
    modelValue?: number[]
    selectable?: boolean
    hasMore?: boolean
    isLoadingMore?: boolean
    backgroundPreset?: GridBackgroundPreset | null
  }>(),
  {
    modelValue: () => [],
    selectable: true,
    hasMore: false,
    isLoadingMore: false,
    backgroundPreset: null,
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: number[]]
  copy: [id: number]
  'load-more': []
}>()

const COLUMNS: { value: number; label: string }[] = [
  { value: 6, label: '6×1' },
  { value: 4, label: '4×1' },
  { value: 2, label: '2×1' },
  { value: 1, label: '1×1' },
]

function closestColumnRatio(sign: GridSign): number {
  if (!sign.width || !sign.height) {
    return sign.column_ratio || 1
  }

  const ratio = sign.width / sign.height
  let closest = COLUMNS[0]!.value
  let minDiff = Math.abs(ratio - closest)

  for (const col of COLUMNS) {
    const diff = Math.abs(ratio - col.value)
    if (diff < minDiff) {
      minDiff = diff
      closest = col.value
    }
  }

  return closest
}

const columns = computed(() => {
  const byRatio: Record<number, GridSign[]> = {}

  for (const col of COLUMNS) {
    byRatio[col.value] = []
  }

  for (const sign of props.signs) {
    const ratio = closestColumnRatio(sign)
    ;(byRatio[ratio] ?? []).push(sign)
  }

  return COLUMNS.map((col) => ({
    label: col.label,
    value: col.value,
    signs: [...(byRatio[col.value] ?? [])],
  }))
})

const gridSurfaceClass = computed(() => getGridBackgroundSurfaceClasses(props.backgroundPreset))

function isSelected(id: number): boolean {
  return props.modelValue.includes(id)
}

function toggleSelect(id: number) {
  const selected = isSelected(id)
    ? props.modelValue.filter((sid) => sid !== id)
    : [...props.modelValue, id]
  emit('update:modelValue', selected)
}

function allSelectedInCol(signs: GridSign[]): boolean {
  return signs.length > 0 && signs.every((s) => isSelected(s.id))
}

function toggleColumn(signs: GridSign[]) {
  if (allSelectedInCol(signs)) {
    const ids = new Set(signs.map((s) => s.id))
    emit(
      'update:modelValue',
      props.modelValue.filter((id) => !ids.has(id)),
    )
  } else {
    const current = new Set(props.modelValue)
    for (const sign of signs) {
      current.add(sign.id)
    }
    emit('update:modelValue', [...current])
  }
}

const sentinel = ref<HTMLElement | null>(null)
let observer: IntersectionObserver | null = null

onMounted(() => {
  observer = new IntersectionObserver(
    (entries) => {
      if (entries[0]?.isIntersecting && props.hasMore && !props.isLoadingMore) {
        emit('load-more')
      }
    },
    { rootMargin: '300px' },
  )
  if (sentinel.value) observer.observe(sentinel.value)
})

onUnmounted(() => {
  observer?.disconnect()
})
</script>

<template>
  <div :class="gridSurfaceClass">
    <div class="grid gap-3 sign-grid">
      <div v-for="col in columns" :key="col.label" class="flex flex-col gap-3">
        <div
          class="text-center text-lg font-semibold group"
          :class="selectable ? 'cursor-pointer select-none' : ''"
          @click="selectable && toggleColumn(col.signs)"
        >
          <div class="flex items-center justify-center gap-4">
            <div v-if="!selectable" class="h-px w-full bg-outline-variant/20"></div>

            <div
              v-if="selectable"
              class="-ml-8 flex size-5 shrink-0 items-center justify-center rounded ring-1 border-0 transition-all"
              :class="
                allSelectedInCol(col.signs)
                  ? 'ring-primary bg-primary'
                  : 'bg-surface-container-low ring-outline-variant group-hover:ring-primary group-hover:bg-surface-container-high'
              "
            >
              <svg
                v-if="allSelectedInCol(col.signs)"
                class="size-3 text-background"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="3"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
              </svg>
            </div>
            <p class="text-on-surface-variant">{{ col.label }}</p>

            <div v-if="!selectable" class="h-px w-full bg-outline-variant/20"></div>
          </div>
        </div>

        <article
          v-for="sign in col.signs"
          :key="sign.id"
          class="group relative cursor-pointer transition duration-150 ease-in-out ring-offset-background ring-offset-2 hover:ring-primary hover:ring-2"
          :class="selectable && isSelected(sign.id) ? 'ring-emerald-400 ring-2' : ''"
          @click="selectable ? toggleSelect(sign.id) : emit('copy', sign.id)"
        >
          <SignMedia :sign="sign" />

          <div
            v-if="selectable"
            class="absolute top-2 left-2 z-10 flex size-5 items-center justify-center rounded border-0 ring transition-all"
            :class="
              isSelected(sign.id)
                ? 'ring-primary bg-primary'
                : 'ring-outline-variant bg-surface-container-low group-hover:bg-surface-container-high group-hover:ring-primary'
            "
          >
            <svg
              v-if="isSelected(sign.id)"
              class="size-4 text-background"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="3"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
          </div>

          <button
            v-if="selectable"
            type="button"
            class="absolute top-2 right-2 z-10 flex size-7 items-center justify-center rounded-full bg-background/50 text-zinc-100/70 opacity-0 transition hover:bg-background/70 hover:text-zinc-100 group-hover:opacity-100"
            title="Copy public URL"
            @click.stop="emit('copy', sign.id)"
          >
            <svg
              class="size-3.5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"
              />
            </svg>
          </button>

          <div
            class="pointer-events-none absolute inset-0 flex items-center justify-center bg-background/30 backdrop-blur-sm transition-opacity duration-200"
            :class="copiedSignId === sign.id ? 'opacity-100' : 'opacity-0'"
          >
            <span class="font-mono text-sm text-zinc-100"> Copied! </span>
          </div>
        </article>
      </div>
    </div>

    <div ref="sentinel" class="h-px" />

    <p v-if="isLoadingMore" class="py-6 text-center font-mono text-xs text-zinc-500">
      Loading more...
    </p>
  </div>
</template>

<style scoped>
.sign-grid {
  grid-template-columns: 6fr 4fr 2fr 1fr;
}

@media (max-width: 1023px) {
  .sign-grid {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 639px) {
  .sign-grid {
    grid-template-columns: 1fr;
  }
}
</style>
