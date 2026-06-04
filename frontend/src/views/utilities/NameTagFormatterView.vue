<script setup lang="ts">
import { ref } from 'vue'
import { useNameTagFormatter } from '@/composables/useNameTagFormatter'
import FormatToolbar from '@/components/FormatToolbar.vue'

const {
  editorRef,
  showToolbar,
  toolbarPosition,
  selectedFormat,
  applySolidColor,
  applyGradient,
  resetFormatting,
  onKeydown,
  onKeyup,
  getTrackmaniaContent,
} = useNameTagFormatter()

const copied = ref(false)
let copyTimeout: ReturnType<typeof setTimeout> | null = null

function copyFormattedContent() {
  const content = getTrackmaniaContent()
  if (!content) return

  navigator.clipboard.writeText(content).then(() => {
    copied.value = true
    if (copyTimeout) clearTimeout(copyTimeout)
    copyTimeout = setTimeout(() => {
      copied.value = false
    }, 2000)
  })
}
</script>

<template>
  <div class="max-w-3xl">
    <h1 class="text-[clamp(1.75rem,3vw,2.25rem)] leading-tight text-zinc-100">
      Name Tag Formatter
    </h1>
    <p class="mt-1 text-sm text-zinc-500">
      Style text with Trackmania&ndash;inspired formatting. Select any portion to apply solid
      colors, gradients, or remove formatting.
    </p>

    <div class="relative mt-8">
      <div
        ref="editorRef"
        contenteditable="true"
        class="min-h-[52px] w-full rounded border border-white/20 bg-surface py-3 pl-4 pr-11 text-lg leading-snug text-zinc-100 transition-colors focus:border-white/30 focus:outline-hidden overflow-hidden whitespace-nowrap"
        :class="{ 'text-zinc-600': !editorRef?.textContent?.trim() }"
        data-placeholder="Type your name tag text here..."
        @keydown="onKeydown"
        @keyup="onKeyup"
      />

      <button
        class="absolute top-2.5 right-3 flex size-8 cursor-pointer items-center justify-center rounded-md text-zinc-500 transition-colors hover:bg-white/5 hover:text-zinc-300"
        :title="copied ? 'Copied!' : 'Copy formatted text'"
        @click="copyFormattedContent"
      >
        <svg
          v-if="!copied"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke-width="1.5"
          stroke="currentColor"
          class="size-6"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184"
          />
        </svg>

        <svg
          v-else
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 20 20"
          fill="currentColor"
          class="h-4 w-4 text-emerald-400"
        >
          <path
            fill-rule="evenodd"
            d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z"
            clip-rule="evenodd"
          />
        </svg>
      </button>

      <p class="mt-3 text-xs text-zinc-600">
        Select text to format &mdash; colors and gradients render live.
      </p>
    </div>

    <FormatToolbar
      :visible="showToolbar"
      :position="toolbarPosition"
      :format="selectedFormat"
      :editor-ref="editorRef"
      @apply-solid="applySolidColor"
      @apply-gradient="applyGradient"
      @reset="resetFormatting"
    />
  </div>
</template>

<style scoped>
[contenteditable]:empty::before {
  content: attr(data-placeholder);
  color: inherit;
  pointer-events: none;
}

[contenteditable]:focus {
  caret-color: #38bdf8;
}
</style>
