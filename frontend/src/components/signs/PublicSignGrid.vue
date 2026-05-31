<script setup lang="ts">
import { computed } from 'vue'

import type { PublicSign } from '@/types/public-folder'

const props = defineProps<{
  signs: PublicSign[]
  copiedSignId: number | null
}>()

const emit = defineEmits<{
  copy: [id: number]
}>()

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
  const byRatio: Record<number, PublicSign[]> = {}

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
    signs: byRatio[col.value] ?? [],
  }))
})
</script>

<template>
  <div class="grid gap-6 sign-grid">
    <div v-for="col in columns" :key="col.label" class="gap-3 flex flex-col">
      <div class="px-4 py-2 text-center text-sm font-semibold text-white">
        <p>
          {{ col.label }}
        </p>
        <p class="text-xs font-mono font-normal text-zinc-500">{{ col.signs.length }} signs</p>
      </div>

      <article
        v-for="sign in col.signs"
        :key="sign.id"
        class="group cursor-pointer relative transition duration-150 ease-in-out ring ring-white hover:ring-4"
        @click="emit('copy', sign.id)"
      >
        <img
          :src="sign.public_url"
          :alt="sign.name"
          loading="lazy"
          class="w-full block transition duration-300 ease-in-out"
        />

        <div
          class="absolute inset-0 flex items-center justify-center bg-black/30 backdrop-blur-sm transition-opacity duration-200"
          :class="copiedSignId === sign.id ? 'opacity-100' : 'opacity-0 pointer-events-none'"
        >
          <span class="px-4 py-2 font-mono text-sm text-white"> Copied! </span>
        </div>
      </article>
    </div>
  </div>
</template>

<style scoped>
.sign-grid {
  grid-template-columns: 4fr 4fr 2fr 1fr;
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
