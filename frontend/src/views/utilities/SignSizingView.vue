<template>
  <div class="max-w-3xl">
    <h1 class="text-[clamp(1.75rem,3vw,2.25rem)] leading-tight text-zinc-100">Sign Sizing Guide</h1>
    <p class="mt-1 text-sm text-zinc-500">
      Reference for Trackmania sign image dimensions and types.
    </p>

    <div class="mt-8 space-y-10 text-sm text-zinc-300 leading-relaxed">
      <section>
        <h2 class="text-base font-semibold text-zinc-100">Sign types</h2>
        <p class="mt-2">
          There are two types of sign images in Trackmania, distinguished by their file format:
        </p>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
          <div class="rounded-xl border border-border bg-surface p-4">
            <div class="flex items-center gap-2 mb-2">
              <span class="rounded bg-amber-500/15 px-2 py-0.5 text-xs font-semibold text-amber-400"
                >JPG</span
              >
              <span class="font-semibold text-zinc-100">Background</span>
            </div>
            <p class="text-zinc-400">
              A solid image that forms the base of a sign. Backgrounds fill the entire sign surface
              and are placed directly in the editor as a standalone sign.
            </p>
          </div>
          <div class="rounded-xl border border-border bg-surface p-4">
            <div class="flex items-center gap-2 mb-2">
              <span class="rounded bg-sky-500/15 px-2 py-0.5 text-xs font-semibold text-sky-400"
                >PNG</span
              >
              <span class="font-semibold text-zinc-100">Overlay</span>
            </div>
            <p class="text-zinc-400">
              A transparent image layered on top of a background. Overlays are placed in the editor
              at the same position as the background sign, sitting in front of it.
            </p>
          </div>
        </div>
      </section>

      <section>
        <h2 class="text-base font-semibold text-zinc-100">Approaches</h2>
        <p class="mt-2 mb-4 text-zinc-400">
          Both approaches are valid. It comes down to your workflow and how much reuse you need.
        </p>
        <div class="space-y-4">
          <div class="rounded-xl border border-border p-4">
            <p class="font-semibold text-zinc-100">Modular: background + overlays</p>
            <p class="mt-1 text-zinc-400">
              Design one JPG background (e.g. a plain colour or texture) and several PNG overlays
              (logos, arrows, text). In the editor you stack them on the same sign slot. This lets
              you mix and match overlays against any background without redesigning each
              combination, which is great for sign packs with a consistent style. Easier for
              <strong class="text-zinc-300">designers</strong>: each piece can be updated or reused
              independently.
            </p>
          </div>
          <div class="rounded-xl border border-border p-4">
            <p class="font-semibold text-zinc-100">All-in-one: single JPG</p>
            <p class="mt-1 text-zinc-400">
              Bake the background and overlay content together into a single JPG. This means only
              one sign to place in the editor, but every unique combination requires its own file.
              Easier for <strong class="text-zinc-300">mappers</strong>: one click to place a
              finished sign, no layering needed.
            </p>
          </div>
        </div>
      </section>

      <section>
        <h2 class="text-base font-semibold text-zinc-100">Image sizes</h2>
        <p class="mt-2 mb-4">
          All signs share a fixed height of <strong class="text-zinc-200">512 px</strong>. The width
          depends on how many block units wide the sign is.
        </p>
        <div class="overflow-x-auto rounded-xl border border-border">
          <table class="w-full text-left text-xs">
            <thead>
              <tr class="border-b border-border bg-surface">
                <th class="px-4 py-3 font-semibold text-zinc-300">Image size</th>
                <th class="px-4 py-3 font-semibold text-zinc-300">Aspect ratio</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <tr class="transition-colors hover:bg-white/[0.02]">
                <td class="px-4 py-3 font-mono text-emerald-400">512 × 512 px</td>
                <td class="px-4 py-3 text-zinc-400">1 : 1</td>
              </tr>
              <tr class="transition-colors hover:bg-white/[0.02]">
                <td class="px-4 py-3 font-mono text-emerald-400">1024 × 512 px</td>
                <td class="px-4 py-3 text-zinc-400">2 : 1</td>
              </tr>
              <tr class="transition-colors hover:bg-white/[0.02]">
                <td class="px-4 py-3 font-mono text-emerald-400">2048 × 512 px</td>
                <td class="px-4 py-3 text-zinc-400">4 : 1</td>
              </tr>
              <tr class="transition-colors hover:bg-white/[0.02]">
                <td class="px-4 py-3 font-mono text-emerald-400">3072 × 512 px</td>
                <td class="px-4 py-3 text-zinc-400">6 : 1</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section>
        <h2 class="text-base font-semibold text-zinc-100">Tips</h2>
        <ul class="mt-2 space-y-2 text-zinc-400">
          <li class="flex items-start gap-2">
            <span class="mt-0.5 text-emerald-400 shrink-0">•</span>
            <span
              >When designing overlays, keep the transparent areas fully transparent.
              Semi-transparent edges can look muddy over certain backgrounds.</span
            >
          </li>
          <li class="flex items-start gap-2">
            <span class="mt-0.5 text-emerald-400 shrink-0">•</span>
            <span
              >Use high contrast between overlay content and the background it will sit on. Colours
              that look vivid in isolation can wash out at a distance.</span
            >
          </li>
          <li class="flex items-start gap-2">
            <span class="mt-0.5 text-emerald-400 shrink-0">•</span>
            <span
              >Keep important content away from the edges. A small amount of padding prevents
              clipping regardless of how the sign is placed.</span
            >
          </li>
        </ul>
      </section>
    </div>
  </div>
</template>
