<script setup lang="ts">
import { RouterView } from 'vue-router'

import UtilityNavLink from '@/components/utilities/UtilityNavLink.vue'
</script>

<template>
  <div class="grid gap-gutter lg:grid-cols-[240px_minmax(0,1fr)]">
    <aside class="h-fit">
      <p class="text-label-md text-primary">Utilities</p>

      <nav class="mt-5 flex flex-col gap-1">
        <UtilityNavLink to="/utilities/sign-sizing">Sign Sizing Guide</UtilityNavLink>
        <UtilityNavLink to="/utilities/name-tag-formatter">Name Tag Formatter</UtilityNavLink>
      </nav>
    </aside>

    <div class="min-w-0 flex-1">
      <RouterView />
    </div>
  </div>
</template>
