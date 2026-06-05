<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'
import { RouterLink } from 'vue-router'
import { ChevronRight } from '@lucide/vue'

type BreadcrumbItem = {
  label: string
  to?: RouteLocationRaw
}

withDefaults(
  defineProps<{
    items: BreadcrumbItem[]
  }>(),
  {},
)
</script>

<template>
  <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-on-surface-variant">
    <template v-for="(item, index) in items" :key="`${item.label}-${index}`">
      <RouterLink
        v-if="item.to && index < items.length - 1"
        :to="item.to"
        class="transition-colors hover:text-on-surface"
      >
        {{ item.label }}
      </RouterLink>
      <span
        v-else
        :class="index === items.length - 1 ? 'text-primary' : ''"
        :aria-current="index === items.length - 1 ? 'page' : undefined"
      >
        {{ item.label }}
      </span>

      <span v-if="index < items.length - 1" aria-hidden="true" class="shrink-0">
        <ChevronRight class="size-4" />
      </span>
    </template>
  </nav>
</template>
