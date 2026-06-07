<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { getGridBackgroundSurfaceClasses } from '@/lib/grid-background-presets'
import SignMedia from '@/components/signs/SignMedia.vue'
import type { GridBackgroundPreset } from '@/types/grid-background'
import UiButton from '../ui/UiButton.vue'
import { Eye } from '@lucide/vue'

interface PreviewSign {
  id: number
  name: string
  public_url: string
  mime_type: string
  width: number | null
  height: number | null
  column_ratio: number | null
}

const props = withDefaults(
  defineProps<{
    signs: PreviewSign[]
    folderSlug?: string
    maxPerColumn?: number
    totalSigns?: number
    backgroundPreset?: GridBackgroundPreset | null
  }>(),
  { maxPerColumn: 6, totalSigns: 0, backgroundPreset: null },
)

const COLUMNS: { value: number; label: string }[] = [
  { value: 6, label: '6×1' },
  { value: 4, label: '4×1' },
  { value: 2, label: '2×1' },
  { value: 1, label: '1×1' },
]

function closestColumnRatio(sign: PreviewSign): number {
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
  const byRatio: Record<number, PreviewSign[]> = {}

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
    signs: [...(byRatio[col.value] ?? [])].slice(0, props.maxPerColumn),
  }))
})

const gridSurfaceClass = computed(() => getGridBackgroundSurfaceClasses(props.backgroundPreset))
const hiddenSignsCount = computed(() => Math.max((props.totalSigns ?? 0) - props.signs.length, 0))
</script>

<template>
  <div class="grid gap-4">
    <div class="-m-2" :class="gridSurfaceClass">
      <div class="grid gap-2 preview-sign-grid">
        <div v-for="col in columns" :key="col.label" class="flex flex-col gap-2">
          <SignMedia v-for="sign in col.signs" :key="sign.id" :sign="sign" />
        </div>
      </div>
    </div>

    <div v-if="folderSlug" class="mt-6 flex flex-col items-center gap-2 pb-1 text-center">
      <div class="flex flex-col items-center gap-2">
        <RouterLink :to="{ name: 'public-folder', params: { slug: folderSlug } }">
          <UiButton>
            <Eye class="size-5" />
            Show all signs
          </UiButton>
        </RouterLink>

        <p v-if="hiddenSignsCount > 0" class="text-xs text-on-surface-variant">
          {{ hiddenSignsCount }} more sign{{ hiddenSignsCount === 1 ? '' : 's' }}
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.preview-sign-grid {
  grid-template-columns: 6fr 4fr 2fr 1fr;
}

@media (max-width: 1023px) {
  .preview-sign-grid {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 639px) {
  .preview-sign-grid {
    grid-template-columns: 1fr;
  }
}
</style>
