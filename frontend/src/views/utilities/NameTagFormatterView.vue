<script setup lang="ts">
import { ref } from 'vue'
import { Check, Copy } from '@lucide/vue'

import { useNameTagFormatter } from '@/composables/useNameTagFormatter'
import FormatToolbar from '@/components/FormatToolbar.vue'
import UtilityPageShell from '@/components/utilities/UtilityPageShell.vue'
import UtilitySection from '@/components/utilities/UtilitySection.vue'

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
  <UtilityPageShell
    title="Name Tag Formatter"
    description="Write, style, and copy Trackmania-ready name tag text with live formatting controls."
  >
    <UtilitySection
      title="Formatter"
      description="Type into the editor, select any portion of text, then use the floating toolbar to apply styling."
    >
      <div class="relative overflow-visible">
        <div
          ref="editorRef"
          contenteditable="true"
          class="min-h-10 w-full rounded-lg border border-outline-variant bg-surface px-4 py-4 pr-14 leading-tight transition-colors focus:border-primary/40 focus:outline-hidden overflow-hidden whitespace-nowrap"
          :class="{ 'text-on-surface-variant/50': !editorRef?.textContent?.trim() }"
          data-placeholder="Type your name tag text here..."
          @keydown="onKeydown"
          @keyup="onKeyup"
        />

        <button
          class="absolute top-1/2 -translate-y-1/2 right-2 flex size-10 cursor-pointer items-center justify-center rounded bg-surface text-on-surface-variant transition hover:border-primary/20 hover:bg-surface-container hover:text-on-surface"
          :title="copied ? 'Copied!' : 'Copy formatted text'"
          @click="copyFormattedContent"
        >
          <Copy v-if="!copied" class="size-5" />
          <Check v-else class="size-5 text-primary" />
        </button>
      </div>
    </UtilitySection>

    <UtilitySection
      title="How To Use It"
      description="The tool is optimized for quick iteration, not a full text editor workflow."
    >
      <div class="grid gap-4 lg:grid-cols-3">
        <article class="rounded-lg border border-outline-variant bg-surface-container-low p-5">
          <p class="text-label-md text-primary">1. Write</p>
          <p class="mt-3 text-body-md text-on-surface-variant">
            Enter the full tag text first so spacing and emphasis are already in place.
          </p>
        </article>

        <article class="rounded-lg border border-outline-variant bg-surface-container-low p-5">
          <p class="text-label-md text-primary">2. Select</p>
          <p class="mt-3 text-body-md text-on-surface-variant">
            Highlight the exact characters you want to recolor or gradient.
          </p>
        </article>

        <article class="rounded-lg border border-outline-variant bg-surface-container-low p-5">
          <p class="text-label-md text-primary">3. Copy</p>
          <p class="mt-3 text-body-md text-on-surface-variant">
            Once the tag looks right, copy it directly and paste it into Trackmania.
          </p>
        </article>
      </div>
    </UtilitySection>

    <FormatToolbar
      :visible="showToolbar"
      :position="toolbarPosition"
      :format="selectedFormat"
      :editor-ref="editorRef"
      @apply-solid="applySolidColor"
      @apply-gradient="applyGradient"
      @reset="resetFormatting"
    />
  </UtilityPageShell>
</template>

<style scoped>
[contenteditable]:empty::before {
  content: attr(data-placeholder);
  color: inherit;
  pointer-events: none;
}

[contenteditable]:focus {
  caret-color: var(--color-primary);
}
</style>
