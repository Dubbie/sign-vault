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
          ? 'border-l-primary bg-primary/10 text-primary'
          : 'border-l-transparent text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface'
      "
      @click="navigate"
    >
      <slot />
    </a>
  </RouterLink>
</template>
