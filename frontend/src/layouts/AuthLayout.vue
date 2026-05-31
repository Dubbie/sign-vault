<script setup lang="ts">
import { RouterView } from 'vue-router'
</script>

<template>
  <div class="relative min-h-screen overflow-hidden bg-black">
    <main class="relative z-10 grid min-h-screen place-items-center p-8">
      <RouterView />
    </main>
  </div>
</template>
