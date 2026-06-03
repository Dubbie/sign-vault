<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

interface PreviewSign {
  id: number
  name: string
  public_url: string
  width: number | null
  height: number | null
}

const props = defineProps<{
  signs: PreviewSign[]
  folderSlug?: string
}>()

const nameCollator = new Intl.Collator(undefined, {
  numeric: true,
  sensitivity: 'base',
})

const COLUMNS: { value: number; label: string }[] = [
  { value: 6, label: '6\u00D71' },
  { value: 4, label: '4\u00D71' },
  { value: 2, label: '2\u00D71' },
  { value: 1, label: '1\u00D71' },
]

function closestColumnRatio(width: number | null, height: number | null): number {
  if (!width || !height) return 1

  const ratio = width / height
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
    const ratio = closestColumnRatio(sign.width, sign.height)
    ;(byRatio[ratio] ?? []).push(sign)
  }

  return COLUMNS.map((col) => ({
    label: col.label,
    value: col.value,
    signs: [...(byRatio[col.value] ?? [])].sort((a, b) => nameCollator.compare(a.name, b.name)),
  }))
})
</script>

<template>
  <div class="relative overflow-hidden">
    <div class="grid gap-2 preview-sign-grid">
      <div v-for="col in columns" :key="col.label" class="flex flex-col gap-2">
        <img
          v-for="sign in col.signs"
          :key="sign.id"
          :src="sign.public_url"
          :alt="sign.name"
          loading="lazy"
          class="block w-full"
        />
      </div>
    </div>

    <div v-if="folderSlug" class="pointer-events-none absolute inset-0 bg-linear-to-b from-transparent to-background">
      <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
        <RouterLink
          :to="{ name: 'public-folder', params: { slug: folderSlug } }"
          class="pointer-events-auto rounded-md border-2 bg-zinc-100 border-background h-9 flex items-center px-3 font-semibold text-background shadow-lg shadow-background backdrop-blur-sm transition hover:bg-zinc-300"
        >
          Show all signs
        </RouterLink>
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
