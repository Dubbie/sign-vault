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
        <RouterLink
          to="/utilities/name-tag-formatter"
          class="rounded-lg px-3 py-2 text-sm text-zinc-400 no-underline transition-colors hover:bg-white/5 hover:text-zinc-100"
          active-class="bg-white/5 text-zinc-100"
        >
          Name Tag Formatter
        </RouterLink>
      </nav>
    </aside>

    <div class="min-w-0 flex-1">
      <RouterView />
    </div>
  </div>
</template>
