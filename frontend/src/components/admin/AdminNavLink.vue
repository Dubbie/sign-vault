<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'
import { RouterLink } from 'vue-router'

defineProps<{
  to: RouteLocationRaw
}>()
</script>

<template>
  <RouterLink :to="to" custom v-slot="{ href, navigate, isActive }">
    <a
      :href="href"
      class="block border-l-2 px-3 py-2 text-sm no-underline transition"
      :class="
        isActive
          ? 'border-l-red-400 bg-red-400/10 text-red-400'
          : 'border-l-transparent text-zinc-500 hover:bg-surface-container-low hover:text-zinc-300'
      "
      @click="navigate"
    >
      <slot />
    </a>
  </RouterLink>
</template>
