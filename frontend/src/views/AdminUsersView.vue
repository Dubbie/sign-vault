<script setup lang="ts">
import { onMounted, ref } from 'vue'

import { banUser, getUsers, unbanUser } from '@/lib/admin'
import type { AdminUser, PaginatedAdminUsers } from '@/types/auth'
import { useAuthStore } from '@/stores/auth'

import UiButton from '@/components/ui/UiButton.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiErrorBanner from '@/components/ui/UiErrorBanner.vue'
import UiFormField from '@/components/ui/UiFormField.vue'
import UiInput from '@/components/ui/UiInput.vue'

const authStore = useAuthStore()

const data = ref<PaginatedAdminUsers | null>(null)
const isLoading = ref(true)
const error = ref<string | null>(null)
const banTarget = ref<AdminUser | null>(null)
const banReason = ref('')
const isBanning = ref(false)
const currentPage = ref(1)

async function loadUsers(page = 1) {
  isLoading.value = true
  error.value = null
  currentPage.value = page

  try {
    data.value = await getUsers(page)
  } catch {
    error.value = 'Failed to load users.'
  } finally {
    isLoading.value = false
  }
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
  <div>
    <div class="mb-6">
      <h1 class="text-[clamp(2rem,4vw,2.5rem)] leading-tight text-zinc-100">Users</h1>
      <p class="mt-1 text-sm text-zinc-400">Manage all registered users</p>
    </div>

    <UiErrorBanner v-if="error">
      {{ error }}
    </UiErrorBanner>

    <p v-if="isLoading" class="mt-4 text-zinc-400">Loading users...</p>

    <div v-else-if="data" class="overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead>
          <tr class="border-b border-white/10 text-zinc-500">
            <th class="py-3 pr-4 font-medium">Discord ID</th>
            <th class="py-3 pr-4 font-medium">Username</th>
            <th class="py-3 pr-4 font-medium">Folders</th>
            <th class="py-3 pr-4 font-medium">Signs</th>
            <th class="py-3 pr-4 font-medium">Status</th>
            <th class="py-3 font-medium">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="user in data.data"
            :key="user.id"
            class="border-b border-white/5 transition hover:bg-white/5"
            :class="{ 'bg-red-950/20': user.banned_at }"
          >
            <td class="py-3 pr-4 font-mono text-xs text-zinc-400">{{ user.discord_id }}</td>
            <td class="py-3 pr-4">
              <div class="flex items-center gap-2">
                <img
                  v-if="user.discord_avatar"
                  :src="user.discord_avatar"
                  :alt="user.discord_username"
                  class="size-6 rounded-full"
                />
                <div>
                  <p class="text-zinc-100">
                    {{ user.discord_global_name || user.discord_username }}
                  </p>
                  <p v-if="user.is_admin" class="text-xs text-emerald-400">Admin</p>
                </div>
              </div>
            </td>
            <td class="py-3 pr-4 text-zinc-400">{{ user.folders_count }}</td>
            <td class="py-3 pr-4 text-zinc-400">{{ user.signs_count }}</td>
            <td class="py-3 pr-4">
              <span
                v-if="user.banned_at"
                class="rounded-full bg-red-900/30 px-2.5 py-0.5 text-xs font-semibold text-red-400"
                title="Reason: {{ user.ban_reason }}"
              >
                Banned
              </span>
              <span v-else class="rounded-full bg-emerald-400/10 px-2.5 py-0.5 text-xs font-semibold text-emerald-400">
                Active
              </span>
            </td>
            <td class="py-3">
              <div v-if="user.id === authStore.user?.id" class="text-xs text-zinc-500">
                You
              </div>
              <div v-else-if="user.banned_at" class="flex items-center gap-2">
                <UiButton variant="secondary" @click="handleUnban(user)">
                  Unban
                </UiButton>
              </div>
              <div v-else>
                <UiButton variant="danger" @click="openBanModal(user)">
                  Ban
                </UiButton>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <nav v-if="data.last_page > 1" class="mt-6 flex items-center justify-center gap-2">
        <UiButton
          variant="secondary"
          :disabled="data.current_page <= 1"
          @click="loadUsers(data.current_page - 1)"
        >
          Previous
        </UiButton>
        <span class="text-sm text-zinc-500">
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

    <UiModal
      :model-value="banTarget !== null"
      title="Ban User"
      @update:model-value="closeBanModal"
    >
      <div v-if="banTarget">
        <p class="text-sm text-zinc-300">
          Ban <strong>{{ banTarget.discord_global_name || banTarget.discord_username }}</strong
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
          <UiButton variant="secondary" @click="closeBanModal"> Cancel </UiButton>
          <UiButton
            variant="danger"
            :disabled="!banReason.trim() || isBanning"
            @click="handleBan"
          >
            {{ isBanning ? 'Banning...' : 'Ban & Nuke Content' }}
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
