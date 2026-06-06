<script setup lang="ts">
import { RouterView } from 'vue-router'
import { ShieldAlert } from '@lucide/vue'

import AdminNavLink from '@/components/admin/AdminNavLink.vue'
</script>

<template>
  <div class="grid gap-gutter lg:grid-cols-[240px_minmax(0,1fr)]">
    <aside class="h-fit">
      <div class="flex items-center gap-2">
        <ShieldAlert class="size-4 text-red-400" />
        <p class="text-label-md text-red-400">Admin Panel</p>
      </div>

      <nav class="mt-5 flex flex-col gap-1">
        <AdminNavLink to="/admin/users">Users</AdminNavLink>
        <AdminNavLink to="/admin/folders">All Folders</AdminNavLink>
        <AdminNavLink to="/admin/logs">Activity Log</AdminNavLink>
      </nav>
    </aside>

    <div class="min-w-0 flex-1">
      <RouterView />
    </div>
  </div>
</template>
