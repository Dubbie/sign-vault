<script setup lang="ts">
import { RouterView } from 'vue-router'
</script>

<template>
  <div class="relative min-h-screen overflow-hidden">
    <div
      class="pointer-events-none fixed -left-16 -top-24 h-72 w-72 rounded-full bg-ambient-left opacity-45 blur-[32px]"
    ></div>
    <div
      class="pointer-events-none fixed -bottom-32 -right-32 h-88 w-88 rounded-full bg-ambient-right opacity-45 blur-[32px]"
    ></div>
    <main class="relative z-10 grid min-h-screen place-items-center p-8">
      <RouterView />
    </main>
  </div>
</template>
