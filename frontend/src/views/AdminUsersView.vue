<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { ShieldAlert, UserX, Users } from '@lucide/vue'

import { banUser, getUsers, unbanUser } from '@/lib/admin'
import type { AdminUser, PaginatedAdminUsers } from '@/types/auth'
import { useAuthStore } from '@/stores/auth'

import UiButton from '@/components/ui/UiButton.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiErrorBanner from '@/components/ui/UiErrorBanner.vue'
import UiFormField from '@/components/ui/UiFormField.vue'
import UiInput from '@/components/ui/UiInput.vue'
import StatCard from '@/components/ui/StatCard.vue'
import UiAvatar from '@/components/ui/UiAvatar.vue'

const authStore = useAuthStore()

const data = ref<PaginatedAdminUsers | null>(null)
const isLoading = ref(true)
const error = ref<string | null>(null)
const banTarget = ref<AdminUser | null>(null)
const banReason = ref('')
const isBanning = ref(false)
const currentPage = ref(1)
const search = ref('')

let searchTimeout: ReturnType<typeof setTimeout> | null = null

async function loadUsers(page = 1) {
  isLoading.value = true
  error.value = null
  currentPage.value = page

  try {
    data.value = await getUsers(page, search.value || undefined)
  } catch {
    error.value = 'Failed to load users.'
  } finally {
    isLoading.value = false
  }
}

function handleSearchInput(value: string) {
  search.value = value
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    void loadUsers(1)
  }, 400)
}

function openBanModal(user: AdminUser) {
  banTarget.value = user
  banReason.value = ''
}

function closeBanModal() {
  banTarget.value = null
  banReason.value = ''
}

async function handleBan() {
  if (!banTarget.value || !banReason.value.trim()) return

  isBanning.value = true
  error.value = null

  try {
    await banUser(banTarget.value.id, banReason.value.trim())
    closeBanModal()
    await loadUsers(currentPage.value)
  } catch {
    error.value = 'Failed to ban user.'
  } finally {
    isBanning.value = false
  }
}

async function handleUnban(user: AdminUser) {
  error.value = null

  try {
    await unbanUser(user.id)
    await loadUsers(currentPage.value)
  } catch {
    error.value = 'Failed to unban user.'
  }
}

onMounted(() => {
  void loadUsers()
})
</script>

<template>
  <div class="space-y-gutter">
    <div class="flex items-end justify-between gap-4">
      <h1 class="text-headline-xl text-on-surface">Users</h1>
    </div>

    <div class="grid gap-6 sm:grid-cols-3">
      <StatCard
        :icon="Users"
        label="Total users"
        :value="data ? data.stats.total.toLocaleString() : '—'"
      />
      <StatCard
        :icon="ShieldAlert"
        label="Admins"
        :value="data ? data.stats.admins.toLocaleString() : '—'"
      />
      <StatCard
        :icon="UserX"
        label="Banned"
        :value="data ? data.stats.banned.toLocaleString() : '—'"
      />
    </div>

    <UiErrorBanner v-if="error">
      {{ error }}
    </UiErrorBanner>

    <div class="space-y-4">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-baseline gap-3">
          <h2 class="text-headline-md text-on-surface">User list</h2>
          <span v-if="data" class="text-label-md text-on-surface-variant">
            {{ data.total }} total
          </span>
        </div>
        <UiInput
          class="w-full sm:w-64"
          :model-value="search"
          placeholder="Search by username..."
          @update:model-value="handleSearchInput"
        />
      </div>

      <p v-if="isLoading" class="text-on-surface-variant">Loading users...</p>

      <div v-else-if="data">
        <p v-if="data.data.length === 0" class="text-on-surface-variant">
          {{ search ? 'No users match your search.' : 'No users found.' }}
        </p>

        <template v-else>
          <div class="hidden sm:block glass-card overflow-hidden rounded-lg">
            <div class="overflow-x-auto">
              <table class="min-w-full">
                <thead class="bg-surface-container-low">
                  <tr class="text-left">
                    <th class="px-5 py-4 text-label-sm text-on-surface-variant">User</th>
                    <th
                      class="px-5 py-4 text-label-sm text-on-surface-variant text-center max-sm:hidden"
                    >
                      Discord
                    </th>
                    <th
                      class="px-5 py-4 text-label-sm text-on-surface-variant text-center max-sm:hidden"
                    >
                      Trackmania
                    </th>
                    <th
                      class="px-5 py-4 text-label-sm text-on-surface-variant text-right max-md:hidden"
                    >
                      Folders
                    </th>
                    <th
                      class="px-5 py-4 text-label-sm text-on-surface-variant text-right max-md:hidden"
                    >
                      Signs
                    </th>
                    <th class="px-5 py-4 text-label-sm text-on-surface-variant">Status</th>
                    <th class="px-5 py-4 text-label-sm text-on-surface-variant text-right">
                      Actions
                    </th>
                  </tr>
                </thead>

                <tbody>
                  <tr
                    v-for="user in data.data"
                    :key="user.id"
                    class="group border-t border-outline-variant/60 transition hover:bg-surface-container-low"
                  >
                    <td class="px-5 py-4">
                      <div class="flex items-center gap-3">
                        <UiAvatar
                          :name="user.display_name"
                          :src="user.avatar_url"
                          class="size-8 rounded-full"
                        />
                        <div class="min-w-0">
                          <p class="truncate text-body-md font-semibold text-on-surface">
                            {{ user.display_name }}
                          </p>
                          <p v-if="user.is_admin" class="text-label-sm text-emerald-400">Admin</p>
                        </div>
                      </div>
                    </td>

                    <td class="px-5 py-4 text-center max-sm:hidden">
                      <span
                        v-if="user.providers.some((p) => p.provider === 'discord')"
                        class="text-emerald-400"
                        title="Connected"
                        >✓</span
                      >
                      <span v-else class="text-on-surface-variant/30">—</span>
                    </td>
                    <td class="px-5 py-4 text-center max-sm:hidden">
                      <span
                        v-if="user.providers.some((p) => p.provider === 'trackmania')"
                        class="text-emerald-400"
                        title="Connected"
                        >✓</span
                      >
                      <span v-else class="text-on-surface-variant/30">—</span>
                    </td>

                    <td class="px-5 py-4 text-right max-md:hidden">
                      <span class="text-body-md text-on-surface">{{ user.folders_count }}</span>
                    </td>

                    <td class="px-5 py-4 text-right max-md:hidden">
                      <span class="text-body-md text-on-surface">{{ user.signs_count }}</span>
                    </td>

                    <td class="px-5 py-4">
                      <span
                        v-if="user.banned_at"
                        class="inline-flex items-center rounded-full border border-red-400/20 bg-red-400/10 px-2.5 py-1 text-label-sm text-red-400"
                        :title="`Reason: ${user.ban_reason}`"
                      >
                        Banned
                      </span>
                      <span
                        v-else
                        class="inline-flex items-center rounded-full border border-primary/20 bg-primary/10 px-2.5 py-1 text-label-sm text-primary"
                      >
                        Active
                      </span>
                    </td>

                    <td class="px-5 py-4">
                      <div class="flex items-center justify-end gap-2">
                        <div
                          class="flex items-center gap-2 pointer-events-none opacity-0 transition-all -translate-x-4 group-hover:opacity-100 group-hover:pointer-events-auto group-hover:translate-x-0"
                        >
                          <span
                            v-if="user.id === authStore.user?.id"
                            class="text-label-sm text-on-surface-variant"
                          >
                            You
                          </span>
                          <UiButton
                            v-else-if="user.banned_at"
                            size="sm"
                            variant="secondary"
                            type="button"
                            @click="handleUnban(user)"
                          >
                            Unban
                          </UiButton>
                          <UiButton
                            v-else
                            size="sm"
                            variant="danger"
                            type="button"
                            @click="openBanModal(user)"
                          >
                            Ban
                          </UiButton>
                        </div>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="flex flex-col gap-3 sm:hidden">
            <div v-for="user in data.data" :key="user.id" class="glass-card rounded-lg p-4">
              <div class="flex items-center gap-3">
                <UiAvatar
                  :name="user.display_name"
                  :src="user.avatar_url"
                  text-class="text-sm"
                  class="size-10 rounded-full"
                />
                <div class="min-w-0 flex-1">
                  <p class="truncate text-body-md font-semibold text-on-surface">
                    {{ user.display_name }}
                  </p>
                  <p v-if="user.is_admin" class="text-label-sm text-emerald-400">Admin</p>
                </div>
                <span
                  v-if="user.banned_at"
                  class="shrink-0 inline-flex items-center rounded-full border border-red-400/20 bg-red-400/10 px-2.5 py-1 text-label-sm text-red-400"
                  :title="`Reason: ${user.ban_reason}`"
                >
                  Banned
                </span>
                <span
                  v-else
                  class="shrink-0 inline-flex items-center rounded-full border border-primary/20 bg-primary/10 px-2.5 py-1 text-label-sm text-primary"
                >
                  Active
                </span>
              </div>

              <div
                class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-label-sm text-on-surface-variant"
              >
                <span class="flex items-center gap-1.5">
                  Discord
                  <span
                    v-if="user.providers.some((p) => p.provider === 'discord')"
                    class="text-emerald-400"
                    title="Connected"
                    >✓</span
                  >
                  <span v-else class="text-on-surface-variant/30">—</span>
                </span>
                <span class="flex items-center gap-1.5">
                  Trackmania
                  <span
                    v-if="user.providers.some((p) => p.provider === 'trackmania')"
                    class="text-emerald-400"
                    title="Connected"
                    >✓</span
                  >
                  <span v-else class="text-on-surface-variant/30">—</span>
                </span>
                <span>{{ user.folders_count }} folders</span>
                <span>{{ user.signs_count }} signs</span>
              </div>

              <div class="mt-3 flex justify-end">
                <span
                  v-if="user.id === authStore.user?.id"
                  class="text-label-sm text-on-surface-variant"
                >
                  You
                </span>
                <UiButton
                  v-else-if="user.banned_at"
                  size="sm"
                  variant="secondary"
                  type="button"
                  @click="handleUnban(user)"
                >
                  Unban
                </UiButton>
                <UiButton
                  v-else
                  size="sm"
                  variant="danger"
                  type="button"
                  @click="openBanModal(user)"
                >
                  Ban
                </UiButton>
              </div>
            </div>
          </div>
        </template>

        <nav
          v-if="data.last_page > 1"
          class="mt-6 flex flex-wrap items-center justify-center gap-2"
        >
          <UiButton
            variant="secondary"
            :disabled="data.current_page <= 1"
            @click="loadUsers(data.current_page - 1)"
          >
            Previous
          </UiButton>
          <span class="text-sm text-on-surface-variant">
            Page {{ data.current_page }} of {{ data.last_page }}
          </span>
          <UiButton
            variant="secondary"
            :disabled="data.current_page >= data.last_page"
            @click="loadUsers(data.current_page + 1)"
          >
            Next
          </UiButton>
        </nav>
      </div>
    </div>

    <UiModal :model-value="banTarget !== null" title="Ban User" @update:model-value="closeBanModal">
      <div v-if="banTarget">
        <p class="text-sm text-zinc-300">
          Ban <strong>{{ banTarget.display_name }}</strong
          >? This will:
        </p>
        <ul class="mt-2 list-inside list-disc text-sm text-zinc-400">
          <li>Delete all their folders and signs</li>
          <li>Revoke all active sessions</li>
          <li>Prevent them from logging in</li>
        </ul>

        <UiFormField label="Ban reason" name="reason" class="mt-4">
          <UiInput
            v-model="banReason"
            placeholder="Why is this user being banned?"
            maxlength="500"
            required
          />
        </UiFormField>

        <p v-if="error" class="mt-2 text-sm text-red-400">{{ error }}</p>

        <div class="mt-4 flex justify-end gap-3">
          <UiButton variant="secondary" @click="closeBanModal">Cancel</UiButton>
          <UiButton variant="danger" :disabled="!banReason.trim() || isBanning" @click="handleBan">
            {{ isBanning ? 'Banning...' : 'Ban & Nuke Content' }}
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
