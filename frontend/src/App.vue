<script setup lang="ts">
import { RouterView } from 'vue-router'
</script>

<template>
  <div class="app-shell">
    <div class="ambient ambient-left"></div>
    <div class="ambient ambient-right"></div>
    <main class="app-frame">
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
.app-shell {
  position: relative;
  min-height: 100vh;
  overflow: hidden;
}

.ambient {
  position: absolute;
  inset: auto;
  border-radius: 999px;
  filter: blur(32px);
  opacity: 0.45;
  pointer-events: none;
}

.ambient-left {
  top: -6rem;
  left: -4rem;
  width: 18rem;
  height: 18rem;
  background: rgba(56, 189, 248, 0.24);
}

.ambient-right {
  right: -8rem;
  bottom: -8rem;
  width: 22rem;
  height: 22rem;
  background: rgba(99, 102, 241, 0.24);
}

.app-frame {
  position: relative;
  z-index: 1;
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 2rem;
}
</style>
