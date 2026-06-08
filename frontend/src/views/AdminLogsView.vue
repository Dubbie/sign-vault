<script setup lang="ts">
import { onMounted, ref } from 'vue'

import { getActivityLogs } from '@/lib/admin'
import type { ActivityLogEntry, PaginatedActivityLogs } from '@/types/activity-log'

import UiButton from '@/components/ui/UiButton.vue'
import UiAvatar from '@/components/ui/UiAvatar.vue'
import UiErrorBanner from '@/components/ui/UiErrorBanner.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiSelect from '@/components/ui/UiSelect.vue'

const EVENT_OPTIONS = [
  { value: '', label: 'All events' },
  { value: 'admin.user.banned', label: 'Admin - Ban user' },
  { value: 'admin.user.unbanned', label: 'Admin - Unban user' },
  { value: 'admin.folder.deleted', label: 'Admin - Delete folder' },
  { value: 'admin.sign.deleted', label: 'Admin - Delete sign' },
  { value: 'auth.registered', label: 'Auth - Register' },
  { value: 'auth.login', label: 'Auth - Login' },
  { value: 'auth.logout', label: 'Auth - Logout' },
  { value: 'auth.provider.linked', label: 'Auth - Link provider' },
  { value: 'auth.provider.unlinked', label: 'Auth - Unlink provider' },
  { value: 'folder.created', label: 'Content - Folder created' },
  { value: 'folder.deleted', label: 'Content - Folder deleted' },
  { value: 'folder.visibility_changed', label: 'Content - Visibility changed' },
  { value: 'signs.uploaded', label: 'Content - Signs uploaded' },
  { value: 'signs.deleted', label: 'Content - Signs deleted' },
]

const data = ref<PaginatedActivityLogs | null>(null)
const isLoading = ref(true)
const error = ref<string | null>(null)
const currentPage = ref(1)
const filterEvent = ref('')
const filterDateFrom = ref('')
const filterDateTo = ref('')

async function loadLogs(page = 1) {
  isLoading.value = true
  error.value = null
  currentPage.value = page

  try {
    data.value = await getActivityLogs({
      page,
      event: filterEvent.value || undefined,
      date_from: filterDateFrom.value || undefined,
      date_to: filterDateTo.value || undefined,
    })
  } catch {
    error.value = 'Failed to load activity logs.'
  } finally {
    isLoading.value = false
  }
}

function applyFilters() {
  void loadLogs(1)
}

function formatRelative(isoString: string): string {
  const diff = Date.now() - new Date(isoString).getTime()
  const seconds = Math.floor(diff / 1000)
  if (seconds < 60) return 'just now'
  const minutes = Math.floor(seconds / 60)
  if (minutes < 60) return `${minutes}m ago`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours}h ago`
  const days = Math.floor(hours / 24)
  return `${days}d ago`
}

function eventBadgeClass(event: string): string {
  if (event.startsWith('admin.')) return 'border-red-400/20 bg-red-400/10 text-red-400'
  if (event.startsWith('auth.')) return 'border-blue-400/20 bg-blue-400/10 text-blue-400'
  return 'border-outline-variant/30 bg-surface-container text-on-surface-variant'
}

function eventLabel(event: string): string {
  return EVENT_OPTIONS.find((o) => o.value === event)?.label ?? event
}

function subjectLabel(entry: ActivityLogEntry): string | null {
  if (entry.subject_user) return entry.subject_user.display_name
  const m = entry.metadata
  if (!m) return null
  if (typeof m.folder_name === 'string') return m.folder_name
  if (typeof m.sign_name === 'string') return m.sign_name
  return null
}

function detailsText(entry: ActivityLogEntry): string {
  const m = entry.metadata
  if (!m) return ''
  const parts: string[] = []
  if (typeof m.reason === 'string') parts.push(`Reason: ${m.reason}`)
  if (typeof m.provider === 'string') parts.push(`Provider: ${m.provider}`)
  if (typeof m.from === 'string' && typeof m.to === 'string') parts.push(`${m.from} → ${m.to}`)
  if (typeof m.count === 'number') parts.push(`${m.count} file${m.count !== 1 ? 's' : ''}`)
  return parts.join(' · ')
}

onMounted(() => {
  void loadLogs()
})
</script>

<template>
  <div class="space-y-gutter">
    <div class="flex items-end justify-between gap-4">
      <h1 class="text-headline-xl text-on-surface">Activity Log</h1>
    </div>

    <UiErrorBanner v-if="error">
      {{ error }}
    </UiErrorBanner>

    <div class="space-y-4">
      <div class="flex flex-wrap items-end gap-3">
        <div class="w-64">
          <p class="mb-1 text-label-sm text-on-surface-variant">Event</p>
          <UiSelect
            v-model="filterEvent"
            name="event-filter"
            :options="EVENT_OPTIONS"
            placeholder="All events"
            @update:model-value="applyFilters"
          />
        </div>

        <div>
          <p class="mb-1 text-label-sm text-on-surface-variant">From</p>
          <UiInput v-model="filterDateFrom" type="date" class="w-40" @change="applyFilters" />
        </div>

        <div>
          <p class="mb-1 text-label-sm text-on-surface-variant">To</p>
          <UiInput v-model="filterDateTo" type="date" class="w-40" @change="applyFilters" />
        </div>
      </div>

      <div class="flex items-baseline gap-3">
        <h2 class="text-headline-md text-on-surface">Events</h2>
        <span v-if="data" class="text-label-md text-on-surface-variant">
          {{ data.total }} total
        </span>
      </div>

      <p v-if="isLoading" class="text-on-surface-variant">Loading...</p>

      <div v-else-if="data">
        <p v-if="data.data.length === 0" class="text-on-surface-variant">No events found.</p>

        <div v-else class="glass-card overflow-hidden rounded-lg">
          <div class="overflow-x-auto">
            <table class="min-w-full table-fixed">
              <colgroup>
                <col class="w-28" />
                <col class="w-52" />
                <col class="w-44" />
                <col class="w-44 max-md:hidden" />
                <col class="w-56 max-lg:hidden" />
                <col class="w-36 max-lg:hidden" />
              </colgroup>

              <thead class="bg-surface-container-low">
                <tr class="text-left">
                  <th class="px-4 py-3 text-xs text-on-surface-variant">When</th>
                  <th class="px-4 py-3 text-xs text-on-surface-variant">Event</th>
                  <th class="px-4 py-3 text-xs text-on-surface-variant">Actor</th>
                  <th class="px-4 py-3 text-xs text-on-surface-variant max-md:hidden">Subject</th>
                  <th class="px-4 py-3 text-xs text-on-surface-variant max-lg:hidden">Details</th>
                  <th class="px-4 py-3 text-xs text-on-surface-variant max-lg:hidden">IP</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="entry in data.data"
                  :key="entry.id"
                  class="border-t border-outline-variant/60 transition hover:bg-surface-container-low"
                >
                  <td class="px-4 py-3 text-xs text-on-surface-variant whitespace-nowrap">
                    <span :title="entry.created_at">{{ formatRelative(entry.created_at) }}</span>
                  </td>

                  <td class="px-4 py-3">
                    <span
                      :title="eventLabel(entry.event)"
                      class="inline-flex max-w-full items-center rounded-full border px-2 py-1 text-xs whitespace-nowrap"
                      :class="eventBadgeClass(entry.event)"
                    >
                      <span class="truncate">{{ eventLabel(entry.event) }}</span>
                    </span>
                  </td>

                  <td class="px-4 py-3">
                    <div v-if="entry.actor" class="flex min-w-0 items-center gap-2">
                      <UiAvatar
                        :name="entry.actor.display_name"
                        :src="entry.actor.avatar_url"
                        class="size-6 rounded-full"
                      />
                      <span
                        class="block truncate text-sm text-on-surface"
                        :title="entry.actor.display_name"
                        >{{ entry.actor.display_name }}</span
                      >
                    </div>
                    <span v-else class="text-on-surface-variant/50">—</span>
                  </td>

                  <td class="px-4 py-3 text-sm text-on-surface-variant max-md:hidden">
                    <span
                      v-if="subjectLabel(entry)"
                      class="block truncate"
                      :title="subjectLabel(entry) ?? undefined"
                    >
                      {{ subjectLabel(entry) }}
                    </span>
                    <span v-else class="text-on-surface-variant/50">—</span>
                  </td>

                  <td class="px-4 py-3 text-sm text-on-surface-variant max-lg:hidden">
                    <span
                      v-if="detailsText(entry)"
                      class="block truncate"
                      :title="detailsText(entry)"
                    >
                      {{ detailsText(entry) }}
                    </span>
                    <span v-else class="text-on-surface-variant/50">—</span>
                  </td>

                  <td class="px-4 py-3 max-lg:hidden">
                    <span
                      v-if="entry.ip_address"
                      :title="entry.ip_address"
                      class="block truncate font-mono text-[11px] text-on-surface-variant/70"
                    >
                      {{ entry.ip_address }}
                    </span>
                    <span v-else class="text-on-surface-variant/50">—</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <nav v-if="data.last_page > 1" class="mt-6 flex items-center justify-center gap-2">
          <UiButton
            variant="secondary"
            :disabled="data.current_page <= 1"
            @click="loadLogs(data.current_page - 1)"
          >
            Previous
          </UiButton>
          <span class="text-sm text-on-surface-variant">
            Page {{ data.current_page }} of {{ data.last_page }}
          </span>
          <UiButton
            variant="secondary"
            :disabled="data.current_page >= data.last_page"
            @click="loadLogs(data.current_page + 1)"
          >
            Next
          </UiButton>
        </nav>
      </div>
    </div>
  </div>
</template>
