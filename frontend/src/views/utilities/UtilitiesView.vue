<script setup lang="ts">
import { RouterLink, RouterView } from 'vue-router'
</script>

<template>
  <div class="flex gap-8">
    <aside class="w-52 shrink-0">
      <p class="mb-3 text-xs font-medium text-emerald-400">Utilities</p>
      <nav class="flex flex-col gap-1">
        <RouterLink
          to="/utilities/sign-sizing"
          class="rounded-lg px-3 py-2 text-sm text-zinc-400 no-underline transition-colors hover:bg-white/5 hover:text-zinc-100"
          active-class="bg-white/5 text-zinc-100"
        >
          Sign Sizing Guide
        </RouterLink>
        <span
          class="cursor-not-allowed rounded-lg px-3 py-2 text-sm text-zinc-600 select-none"
          title="Coming soon"
        >
          Name Tag Formatter
          <span class="ml-1.5 rounded bg-zinc-800 px-1.5 py-0.5 text-[10px] text-zinc-500"
            >Soon</span
          >
        </span>
      </nav>
    </aside>

    <div class="min-w-0 flex-1">
      <RouterView />
    </div>
  </div>
</template>
