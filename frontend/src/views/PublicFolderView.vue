<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

import {
  getPublicFolder,
  getPublicFolderErrorMessage,
  unlockPublicFolder,
} from '@/lib/public-folders'
import type {
  PublicFolder,
  PublicFolderContentsResponse,
  PublicSign,
} from '@/types/public-folder'

const route = useRoute()

const folderSlug = computed(() => String(route.params.slug))

const folder = ref<PublicFolder | null>(null)
const signs = ref<PublicSign[]>([])
const requiresPassword = ref(false)
const isLoading = ref(false)
const isUnlocking = ref(false)
const error = ref<string | null>(null)
const copiedSignId = ref<number | null>(null)

const unlockForm = reactive({
  password: '',
})

function formatDimensions(sign: PublicSign) {
  if (sign.width && sign.height) {
    return `${sign.width} × ${sign.height}`
  }

  return 'Unavailable'
}

function visibilityLabel(visibility: string) {
  return visibility.charAt(0).toUpperCase() + visibility.slice(1)
}

function normalizeFolderResponse(response: PublicFolderContentsResponse) {
  folder.value = response.folder
  signs.value = response.signs
  requiresPassword.value = false
  error.value = null
}

function resetStateForPasswordPrompt() {
  folder.value = null
  signs.value = []
  requiresPassword.value = true
}

function clearUnlockForm() {
  unlockForm.password = ''
}

async function loadPublicFolder() {
  isLoading.value = true
  error.value = null

  try {
    const response = await getPublicFolder(folderSlug.value)

    if ('requires_password' in response) {
      resetStateForPasswordPrompt()
      return
    }

    normalizeFolderResponse(response)
  } catch (exception) {
    const axiosError = exception as { response?: { status?: number } }

    if (axiosError.response?.status === 404) {
      error.value = 'Folder not found.'
      folder.value = null
      signs.value = []
      requiresPassword.value = false
      return
    }

    error.value = getPublicFolderErrorMessage(exception)
  } finally {
    isLoading.value = false
  }
}

async function handleUnlock() {
  error.value = null
  isUnlocking.value = true

  try {
    const response = await unlockPublicFolder(folderSlug.value, {
      password: unlockForm.password,
    })

    normalizeFolderResponse(response)
    clearUnlockForm()
  } catch (exception) {
    const axiosError = exception as { response?: { status?: number } }

    if (axiosError.response?.status === 404) {
      error.value = 'Folder not found.'
      folder.value = null
      signs.value = []
      requiresPassword.value = false
      return
    }

    error.value = getPublicFolderErrorMessage(exception)
  } finally {
    isUnlocking.value = false
  }
}

async function handleCopy(sign: PublicSign) {
  error.value = null

  try {
    await navigator.clipboard.writeText(sign.public_url)
    copiedSignId.value = sign.id

    window.setTimeout(() => {
      if (copiedSignId.value === sign.id) {
        copiedSignId.value = null
      }
    }, 1500)
  } catch {
    error.value = 'Could not copy the sign URL. Please copy it manually.'
  }
}

onMounted(loadPublicFolder)

watch(folderSlug, () => {
  clearUnlockForm()
  folder.value = null
  signs.value = []
  requiresPassword.value = false
  copiedSignId.value = null
  void loadPublicFolder()
})
</script>

<template>
  <section class="page-card">
    <p class="eyebrow">Public folder</p>
    <h1>Shared folder</h1>

    <p v-if="isLoading && !folder && !requiresPassword" class="muted">Loading folder...</p>

    <p v-if="error" class="error-banner">
      {{ error }}
    </p>

    <div v-if="requiresPassword" class="panel">
      <div class="panel-header">
        <div>
          <p class="section-eyebrow">Password required</p>
          <h2>This folder is protected</h2>
        </div>
      </div>

      <form class="form" @submit.prevent="handleUnlock">
        <label>
          <span>Password</span>
          <input
            v-model="unlockForm.password"
            type="password"
            name="password"
            autocomplete="current-password"
            required
          />
        </label>

        <div class="actions">
          <button class="primary-button" type="submit" :disabled="isUnlocking">
            {{ isUnlocking ? 'Unlocking...' : 'Unlock folder' }}
          </button>
        </div>
      </form>
    </div>

    <div v-else-if="folder" class="content">
      <header class="folder-header">
        <div class="folder-copy">
          <h2>{{ folder.name }}</h2>
          <p class="slug">{{ folder.slug }}</p>
        </div>

        <span class="badge">{{ visibilityLabel(folder.visibility) }}</span>
      </header>

      <section class="panel">
        <div class="panel-header">
          <div>
            <p class="section-eyebrow">Signs</p>
            <h3>Available signs</h3>
          </div>

          <p class="section-note">{{ signs.length }} total</p>
        </div>

        <p v-if="signs.length === 0" class="empty-state">No signs available.</p>

        <div v-else class="sign-grid">
          <article v-for="sign in signs" :key="sign.id" class="sign-card">
            <div class="thumb-wrap">
              <img class="thumb" :src="sign.public_url" :alt="sign.name" loading="lazy" />
            </div>

            <div class="sign-body">
              <div class="sign-top">
                <div>
                  <h3>{{ sign.name }}</h3>
                </div>

                <button class="copy-button" type="button" @click="handleCopy(sign)">
                  {{ copiedSignId === sign.id ? 'Copied!' : 'Copy URL' }}
                </button>
              </div>

              <dl class="sign-meta">
                <div>
                  <dt>Dimensions</dt>
                  <dd>{{ formatDimensions(sign) }}</dd>
                </div>
                <div>
                  <dt>Type</dt>
                  <dd>{{ sign.mime_type }}</dd>
                </div>
              </dl>

              <div class="sign-actions">
                <a class="url-link" :href="sign.public_url" target="_blank" rel="noreferrer">
                  Open
                </a>
              </div>
            </div>
          </article>
        </div>
      </section>
    </div>
  </section>
</template>

<style scoped>
.page-card {
  width: min(100%, 72rem);
  padding: 2rem;
  border: 1px solid var(--color-border);
  border-radius: 1.5rem;
  background: var(--color-surface);
  box-shadow: var(--shadow-elevated);
  backdrop-filter: blur(18px);
}

.eyebrow,
.section-eyebrow {
  margin-bottom: 0.5rem;
  color: var(--color-primary);
  font-size: 0.85rem;
  font-weight: 600;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}

h1 {
  color: var(--color-heading);
  font-size: clamp(2rem, 4vw, 2.5rem);
  line-height: 1.05;
}

.muted,
.section-note,
.empty-state {
  color: var(--color-text-muted);
}

.error-banner {
  margin-top: 1rem;
  padding: 0.9rem 1rem;
  border: 1px solid rgba(251, 113, 133, 0.35);
  border-radius: 0.9rem;
  color: #fecdd3;
  background: rgba(127, 29, 29, 0.24);
}

.content {
  display: grid;
  gap: 1.25rem;
  margin-top: 1rem;
}

.folder-header,
.panel-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.folder-copy h2,
.panel h2,
.panel h3 {
  color: var(--color-heading);
}

.slug {
  color: var(--color-text-muted);
}

.badge {
  padding: 0.3rem 0.65rem;
  border-radius: 999px;
  border: 1px solid var(--color-border);
  color: var(--color-heading);
  font-size: 0.85rem;
  text-transform: capitalize;
}

.panel {
  padding: 1.25rem;
  border: 1px solid var(--color-border);
  border-radius: 1.25rem;
  background: var(--color-surface-strong);
}

.form {
  display: grid;
  gap: 1rem;
  margin-top: 1rem;
}

label {
  display: grid;
  gap: 0.4rem;
}

span {
  color: var(--color-text-muted);
  font-size: 0.9rem;
}

input {
  width: 100%;
  padding: 0.85rem 1rem;
  border: 1px solid var(--color-border);
  border-radius: 0.9rem;
  background: rgba(2, 6, 23, 0.35);
  color: var(--color-heading);
}

.actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.primary-button {
  padding: 0.85rem 1rem;
  border: 0;
  border-radius: 0.9rem;
  background: linear-gradient(135deg, var(--color-primary), var(--color-primary-strong));
  color: #04111e;
  font-weight: 700;
  cursor: pointer;
}

.sign-grid {
  display: grid;
  gap: 1rem;
  margin-top: 1rem;
}

.sign-card {
  display: grid;
  grid-template-columns: minmax(11rem, 15rem) 1fr;
  gap: 1rem;
  padding: 1rem;
  border: 1px solid var(--color-border);
  border-radius: 1rem;
  background: rgba(2, 6, 23, 0.18);
}

.thumb-wrap {
  overflow: hidden;
  border-radius: 0.9rem;
  border: 1px solid var(--color-border);
  background: rgba(15, 23, 42, 0.7);
  aspect-ratio: 4 / 1;
}

.thumb {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.sign-body {
  display: grid;
  gap: 1rem;
}

.sign-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.sign-meta {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(8rem, 1fr));
  gap: 1rem;
}

.sign-meta dt {
  color: var(--color-text-muted);
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.sign-meta dd {
  margin-top: 0.25rem;
  color: var(--color-heading);
}

.sign-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.copy-button,
.url-link {
  padding: 0.6rem 0.9rem;
  border-radius: 0.8rem;
  border: 1px solid var(--color-border);
  background: transparent;
  color: var(--color-heading);
  text-decoration: none;
  cursor: pointer;
}

.url-link {
  background: rgba(2, 6, 23, 0.12);
}

@media (max-width: 720px) {
  .page-card {
    padding: 1.25rem;
  }

  .folder-header,
  .panel-header,
  .sign-top {
    flex-direction: column;
  }

  .sign-card {
    grid-template-columns: 1fr;
  }

  .thumb-wrap {
    aspect-ratio: 3 / 1;
  }
}
</style>
