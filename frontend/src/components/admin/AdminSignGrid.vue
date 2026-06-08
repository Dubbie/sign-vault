<script setup lang="ts">
import { computed } from 'vue'
import SignMedia from '@/components/signs/SignMedia.vue'

export interface AdminGridSign {
  id: number
  name: string
  variant_id: number | null
  public_url: string
  thumbnail_url: string | null
  mime_type: string
  width: number | null
  height: number | null
  column_ratio: number | null
}

const props = withDefaults(
  defineProps<{
    signs: AdminGridSign[]
    modelValue?: number[]
  }>(),
  { modelValue: () => [] },
)

const emit = defineEmits<{
  'update:modelValue': [ids: number[]]
}>()

const COLUMNS: { value: number; label: string }[] = [
  { value: 6, label: '6×1' },
  { value: 4, label: '4×1' },
  { value: 2, label: '2×1' },
  { value: 1, label: '1×1' },
]

function closestColumnRatio(sign: AdminGridSign): number {
  if (!sign.width || !sign.height) return sign.column_ratio || 1

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
  const byRatio: Record<number, AdminGridSign[]> = {}
  for (const col of COLUMNS) byRatio[col.value] = []
  for (const sign of props.signs) {
    const ratio = closestColumnRatio(sign)
    ;(byRatio[ratio] ?? []).push(sign)
  }
  return COLUMNS.map((col) => ({
    label: col.label,
    value: col.value,
    signs: byRatio[col.value] ?? [],
  }))
})

function isSelected(id: number) {
  return props.modelValue.includes(id)
}

function toggleSelect(id: number) {
  const next = isSelected(id) ? props.modelValue.filter((s) => s !== id) : [...props.modelValue, id]
  emit('update:modelValue', next)
}

function allSelectedInCol(signs: AdminGridSign[]) {
  return signs.length > 0 && signs.every((s) => isSelected(s.id))
}

function toggleColumn(signs: AdminGridSign[]) {
  if (allSelectedInCol(signs)) {
    const ids = new Set(signs.map((s) => s.id))
    emit(
      'update:modelValue',
      props.modelValue.filter((id) => !ids.has(id)),
    )
  } else {
    const current = new Set(props.modelValue)
    for (const sign of signs) current.add(sign.id)
    emit('update:modelValue', [...current])
  }
}
</script>

<template>
  <div class="grid gap-2 admin-sign-grid">
    <div v-for="col in columns" :key="col.label" class="flex flex-col gap-2">
      <button
        v-if="col.signs.length > 0"
        type="button"
        class="group flex items-center gap-2 select-none cursor-pointer"
        @click="toggleColumn(col.signs)"
      >
        <div
          class="flex size-5 shrink-0 items-center justify-center rounded border-0 ring transition-all"
          :class="
            allSelectedInCol(col.signs)
              ? 'ring-red-400 bg-red-400'
              : 'ring-outline-variant bg-surface-container-low group-hover:bg-surface-container-high group-hover:ring-red-400'
          "
        >
          <svg
            v-if="allSelectedInCol(col.signs)"
            class="size-3 text-white"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="3"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
          </svg>
        </div>
        <span class="text-xs text-on-surface-variant group-hover:text-on-surface transition">{{
          col.label
        }}</span>
      </button>

      <div
        v-for="sign in col.signs"
        :key="sign.id"
        class="group relative cursor-pointer ring-offset-background ring-offset-2 transition hover:ring-2 hover:ring-red-400/60"
        :class="isSelected(sign.id) ? 'ring-2 ring-red-400' : ''"
        @click="toggleSelect(sign.id)"
      >
        <SignMedia :sign="sign" />

        <div
          class="absolute left-2 top-2 z-10 flex size-5 items-center justify-center rounded border-0 ring transition-all"
          :class="
            isSelected(sign.id)
              ? 'ring-red-400 bg-red-400'
              : 'ring-outline-variant bg-surface-container-low group-hover:bg-surface-container-high group-hover:ring-red-400'
          "
        >
          <svg
            v-if="isSelected(sign.id)"
            class="size-4 text-white"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="3"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
          </svg>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-sign-grid {
  grid-template-columns: 6fr 4fr 2fr 1fr;
}

@media (max-width: 1023px) {
  .admin-sign-grid {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 639px) {
  .admin-sign-grid {
    grid-template-columns: 1fr;
  }
}
</style>
