<script setup lang="ts">
withDefaults(
  defineProps<{
    id: number
    name: string
    publicUrl: string
    mimeType: string
    width: number | null
    height: number | null
    sizeBytes?: number
    copied: boolean
    showDelete?: boolean
    showSize?: boolean
  }>(),
  { showDelete: false, showSize: false },
)

const emit = defineEmits<{
  copy: [id: number]
  delete: [id: number]
}>()

function formatDimensions(w: number | null, h: number | null) {
  if (w && h) return `${w} \u00D7 ${h}`
  return 'Unavailable'
}

function formatFileSize(bytes: number) {
  if (bytes < 1024) return `${bytes} B`
  const units = ['KB', 'MB', 'GB', 'TB']
  let value = bytes / 1024
  let unitIndex = 0
  while (value >= 1024 && unitIndex < units.length - 1) {
    value /= 1024
    unitIndex += 1
  }
  return `${value.toFixed(value >= 10 ? 0 : 1)} ${units[unitIndex]}`
}
</script>

<template>
  <article
    class="grid grid-cols-[minmax(11rem,15rem)_1fr] gap-4 rounded-xl border border-border bg-surface-card p-4 max-sm:grid-cols-1"
  >
    <div
      class="overflow-hidden rounded-xl border border-border bg-surface-thumb"
      style="aspect-ratio: 4 / 1"
    >
      <img
        :src="publicUrl"
        :alt="name"
        loading="lazy"
        class="block h-full w-full object-cover"
      />
    </div>

    <div class="grid gap-4">
      <div class="flex items-start justify-between gap-4 max-sm:flex-col">
        <h3 class="text-[1.1rem] text-heading">{{ name }}</h3>
        <button
          type="button"
          class="min-w-[6.5rem] cursor-pointer rounded-xl border border-border bg-transparent px-[0.9rem] py-[0.6rem] text-heading transition duration-150 ease-in-out hover:bg-white/5"
          @click="emit('copy', id)"
        >
          {{ copied ? 'Copied!' : 'Copy URL' }}
        </button>
      </div>

      <dl class="grid grid-cols-[repeat(auto-fit,minmax(8rem,1fr))] gap-4">
        <div>
          <dt class="text-[0.8rem] uppercase tracking-[0.08em] text-text-muted">Dimensions</dt>
          <dd class="mt-1 text-heading">{{ formatDimensions(width, height) }}</dd>
        </div>
        <div v-if="showSize && sizeBytes !== undefined">
          <dt class="text-[0.8rem] uppercase tracking-[0.08em] text-text-muted">Size</dt>
          <dd class="mt-1 text-heading">{{ formatFileSize(sizeBytes) }}</dd>
        </div>
        <div>
          <dt class="text-[0.8rem] uppercase tracking-[0.08em] text-text-muted">Type</dt>
          <dd class="mt-1 text-heading">{{ mimeType }}</dd>
        </div>
      </dl>

      <div class="flex flex-wrap gap-3">
        <a
          :href="publicUrl"
          target="_blank"
          rel="noreferrer"
          class="cursor-pointer rounded-xl border border-border bg-surface-input px-[0.9rem] py-[0.6rem] text-heading no-underline transition duration-150 ease-in-out hover:bg-white/5"
        >
          Open
        </a>
        <button
          v-if="showDelete"
          type="button"
          class="cursor-pointer rounded-xl border border-border-danger bg-transparent px-[0.9rem] py-[0.6rem] text-danger-text transition duration-150 ease-in-out hover:bg-white/5"
          @click="emit('delete', id)"
        >
          Delete
        </button>
      </div>
    </div>
  </article>
</template>
