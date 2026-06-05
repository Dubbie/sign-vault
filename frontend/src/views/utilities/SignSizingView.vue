<script setup lang="ts">
import UtilityPageShell from '@/components/utilities/UtilityPageShell.vue'
import UtilitySection from '@/components/utilities/UtilitySection.vue'

const sizeRows = [
  { width: '512 x 512 px', ratio: '1 : 1' },
  { width: '1024 x 512 px', ratio: '2 : 1' },
  { width: '2048 x 512 px', ratio: '4 : 1' },
  { width: '3072 x 512 px', ratio: '6 : 1' },
] as const

const tips = [
  'Keep transparent regions fully transparent. Soft leftover pixels can turn muddy on bright backgrounds.',
  'Push contrast harder than you think. Trackside readability falls off quickly at speed and distance.',
  'Leave padding around important text and logos so placement or scaling never clips the edges.',
] as const
</script>

<template>
  <UtilityPageShell
    title="Sign Sizing Guide"
    description="Reference for Trackmania sign image dimensions and types."
  >
    <UtilitySection
      title="Image Sizes"
      description=" All signs share a fixed height of 512 px. The width depends on how many block units wide the sign is. "
    >
      <div
        class="overflow-x-auto rounded-lg border border-outline-variant bg-surface-container-low"
      >
        <table class="min-w-full">
          <thead class="bg-surface-container">
            <tr>
              <th class="px-3 py-3 text-left text-label-sm text-on-surface-variant">
                Aspect ratio
              </th>
              <th class="px-3 py-3 text-left text-label-sm text-on-surface-variant">Image size</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in sizeRows"
              :key="row.width"
              class="border-t border-outline-variant/60 transition hover:bg-surface-container"
            >
              <td class="px-3 py-2 text-body-md text-on-surface">{{ row.ratio }}</td>
              <td class="px-3 py-2 font-mono text-body-md text-primary">{{ row.width }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </UtilitySection>

    <UtilitySection
      title="Sign Types"
      description="Use the right file type depending on whether the sign is a base layer or an overlay."
    >
      <div class="grid gap-4 lg:grid-cols-2">
        <article class="rounded-lg border border-outline-variant bg-surface-container-low p-5">
          <div class="flex items-center gap-3">
            <span class="rounded-full bg-tertiary/15 px-3 py-1 text-label-sm text-tertiary"
              >JPG</span
            >
            <h3 class="text-body-lg font-semibold text-on-surface">Background</h3>
          </div>
          <p class="mt-4 text-body-md text-on-surface-variant">
            A solid image that covers the full sign surface. Use it for colors, textures, and base
            composition that should always appear together.
          </p>
        </article>

        <article class="rounded-lg border border-outline-variant bg-surface-container-low p-5">
          <div class="flex items-center gap-3">
            <span class="rounded-full bg-primary/15 px-3 py-1 text-label-sm text-primary">PNG</span>
            <h3 class="text-body-lg font-semibold text-on-surface">Overlay</h3>
          </div>
          <p class="mt-4 text-body-md text-on-surface-variant">
            A transparent layer placed over a background sign. Use it for text, arrows, branding,
            and interchangeable foreground elements.
          </p>
        </article>
      </div>
    </UtilitySection>

    <UtilitySection
      title="Workflow Approaches"
      description="Both approaches are valid. It comes down to your workflow and how much reuse you need."
    >
      <div class="grid gap-4 lg:grid-cols-2">
        <article class="rounded-lg border border-outline-variant bg-surface-container-low p-5">
          <p class="text-label-md text-primary">Flexible system</p>
          <h3 class="mt-2 text-body-lg font-semibold text-on-surface">
            Modular: background + overlays
          </h3>
          <p class="mt-3 text-body-md text-on-surface-variant">
            Design one JPG background (e.g. a plain colour or texture) and several PNG overlays
            (logos, arrows, text). In the editor you stack them on the same sign slot. This lets you
            mix and match overlays against any background without redesigning each combination,
            which is great for sign packs with a consistent style. Easier for
            <b class="text-on-surface">designers</b>: each piece can be updated or reused
            independently.
          </p>
        </article>

        <article class="rounded-lg border border-outline-variant bg-surface-container-low p-5">
          <p class="text-label-md text-secondary">Fast placement</p>
          <h3 class="mt-2 text-body-lg font-semibold text-on-surface">All-in-one: single JPG</h3>
          <p class="mt-3 text-body-md text-on-surface-variant">
            Bake the background and overlay content together into a single JPG. This means only one
            sign to place in the editor, but every unique combination requires its own file. Easier
            for <b class="text-on-surface">mappers</b>: one click to place a finished sign, no
            layering needed.
          </p>
        </article>
      </div>
    </UtilitySection>

    <UtilitySection title="Tips">
      <ul class="list-disc pl-4">
        <li v-for="tip in tips" :key="tip">
          <p class="text-body-md text-on-surface-variant">{{ tip }}</p>
        </li>
      </ul>
    </UtilitySection>
  </UtilityPageShell>
</template>
