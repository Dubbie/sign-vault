<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import { useFoldersStore } from '@/stores/folders'
import { useSignsStore } from '@/stores/signs'
import type { CreateSignPayload, Sign } from '@/types/sign'

const foldersStore = useFoldersStore()
const signsStore = useSignsStore()
const route = useRoute()

const folderId = computed(() => Number(route.params.id))
const folder = computed(() => foldersStore.currentFolder)

const uploadForm = reactive({
  name: '',
  description: '',
})
const selectedFile = ref<File | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)
const copiedSignId = ref<number | null>(null)

const allowedMimeTypes = new Set(['image/png', 'image/jpeg', 'image/webp'])
const dateFormatter = new Intl.DateTimeFormat(undefined, {
  dateStyle: 'medium',
  timeStyle: 'short',
})

function formatDate(value: string) {
  return dateFormatter.format(new Date(value))
}

function formatFileSize(bytes: number) {
  if (bytes < 1024) {
    return `${bytes} B`
  }

  const units = ['KB', 'MB', 'GB', 'TB']
  let value = bytes / 1024
  let unitIndex = 0

  while (value >= 1024 && unitIndex < units.length - 1) {
    value /= 1024
    unitIndex += 1
  }

  return `${value.toFixed(value >= 10 ? 0 : 1)} ${units[unitIndex]}`
}

function visibilityLabel(visibility: string) {
  return visibility.charAt(0).toUpperCase() + visibility.slice(1)
}

function resetUploadForm() {
  uploadForm.name = ''
  uploadForm.description = ''
  selectedFile.value = null

  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

function validateSelectedFile(file: File | null) {
  if (!file) {
    return 'File is required.'
  }

  if (!allowedMimeTypes.has(file.type)) {
    return 'File must be a PNG, JPEG, or WebP image.'
  }

  return null
}

async function loadFolder() {
  const id = folderId.value

  if (!Number.isFinite(id)) {
    foldersStore.error = 'Invalid folder id.'
    return
  }

  const loadedFolder = await foldersStore.fetchFolder(id)

  if (loadedFolder) {
    await signsStore.fetchFolderSigns(id)
  }
}

onMounted(loadFolder)

watch(folderId, () => {
  foldersStore.clearCurrentFolder()
  signsStore.clearCurrentSign()
  signsStore.signs = []
  void loadFolder()
})

async function handleSubmit() {
  signsStore.clearError()

  const fileError = validateSelectedFile(selectedFile.value)

  if (fileError) {
    signsStore.error = fileError
    return
  }

  const payload: CreateSignPayload = {
    file: selectedFile.value as File,
  }

  const trimmedName = uploadForm.name.trim()
  const trimmedDescription = uploadForm.description.trim()

  if (trimmedName) {
    payload.name = trimmedName
  }

  if (trimmedDescription) {
    payload.description = trimmedDescription
  }

  const uploadedSign = await signsStore.uploadSign(folderId.value, payload)

  if (uploadedSign) {
    resetUploadForm()
  }
}

function handleFileChange(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0] ?? null

  signsStore.clearError()

  if (!file) {
    selectedFile.value = null
    return
  }

  const fileError = validateSelectedFile(file)

  if (fileError) {
    selectedFile.value = null
    input.value = ''
    signsStore.error = fileError
    return
  }

  selectedFile.value = file
}

async function handleDelete(sign: Sign) {
  const confirmed = window.confirm(`Delete "${sign.name}"?`)

  if (!confirmed) {
    return
  }

  await signsStore.deleteSign(sign.id)
}

async function handleCopy(sign: Sign) {
  const copied = await signsStore.copySignUrl(sign)

  if (!copied) {
    return
  }

  copiedSignId.value = sign.id
  window.setTimeout(() => {
    if (copiedSignId.value === sign.id) {
      copiedSignId.value = null
    }
  }, 1500)
}
</script>

<template>
  <section class="page-card">
    <RouterLink class="back-link" to="/folders">Back to folders</RouterLink>

    <p class="eyebrow">Folder details</p>

    <p v-if="foldersStore.error" class="error-banner">
      {{ foldersStore.error }}
    </p>

    <p v-if="foldersStore.isLoading && !folder" class="muted">Loading folder...</p>

    <div v-else-if="folder" class="content">
      <header class="folder-header">
        <div class="folder-copy">
          <h1>{{ folder.name }}</h1>
          <p class="slug">{{ folder.slug }}</p>
        </div>

        <div class="folder-actions">
          <span class="badge">{{ visibilityLabel(folder.visibility) }}</span>
          <RouterLink class="secondary-link" :to="{ name: 'folders-edit', params: { id: folder.id } }">
            Edit folder
          </RouterLink>
        </div>
      </header>

      <dl class="meta">
        <div>
          <dt>Created</dt>
          <dd>{{ formatDate(folder.created_at) }}</dd>
        </div>
        <div>
          <dt>Updated</dt>
          <dd>{{ formatDate(folder.updated_at) }}</dd>
        </div>
      </dl>

      <section class="panel">
        <div class="panel-header">
          <div>
            <p class="section-eyebrow">Upload Sign</p>
            <h2>Upload a new sign</h2>
          </div>

          <p class="section-note">PNG, JPEG, or WebP</p>
        </div>

        <p v-if="signsStore.error" class="error-banner">
          {{ signsStore.error }}
        </p>

        <form class="form" @submit.prevent="handleSubmit">
          <label>
            <span>File</span>
            <input
              ref="fileInput"
              type="file"
              name="file"
              accept="image/png,image/jpeg,image/webp"
              required
              @change="handleFileChange"
            />
          </label>

          <label>
            <span>Name</span>
            <input
              v-model="uploadForm.name"
              type="text"
              name="name"
              placeholder="Ice Warning"
            />
          </label>

          <label>
            <span>Description</span>
            <textarea
              v-model="uploadForm.description"
              name="description"
              rows="3"
              placeholder="Optional notes about this sign"
            />
          </label>

          <div class="actions">
            <button class="primary-button" type="submit" :disabled="signsStore.isUploading">
              {{ signsStore.isUploading ? 'Uploading...' : 'Upload sign' }}
            </button>
            <p v-if="selectedFile" class="selected-file">
              Selected: {{ selectedFile.name }}
            </p>
          </div>
        </form>
      </section>

      <section class="panel">
        <div class="panel-header">
          <div>
            <p class="section-eyebrow">Signs</p>
            <h2>Folder signs</h2>
          </div>

          <p class="section-note">{{ signsStore.signs.length }} total</p>
        </div>

        <p v-if="signsStore.isLoading" class="muted">Loading signs...</p>
        <p v-else-if="signsStore.signs.length === 0" class="empty-state">
          No signs uploaded yet.
        </p>

        <div v-else class="sign-grid">
          <article v-for="sign in signsStore.signs" :key="sign.id" class="sign-card">
            <div class="thumb-wrap">
              <img class="thumb" :src="sign.public_url" :alt="sign.name" loading="lazy" />
            </div>

            <div class="sign-body">
              <div class="sign-top">
                <div>
                  <h3>{{ sign.name }}</h3>
                  <p v-if="sign.description" class="sign-description">
                    {{ sign.description }}
                  </p>
                </div>

                <button class="copy-button" type="button" @click="handleCopy(sign)">
                  {{ copiedSignId === sign.id ? 'Copied!' : 'Copy URL' }}
                </button>
              </div>

              <dl class="sign-meta">
                <div>
                  <dt>Dimensions</dt>
                  <dd>
                    {{
                      sign.width && sign.height
                        ? `${sign.width} × ${sign.height}`
                        : 'Unavailable'
                    }}
                  </dd>
                </div>
                <div>
                  <dt>Size</dt>
                  <dd>{{ formatFileSize(sign.size_bytes) }}</dd>
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
                <button class="danger-button" type="button" @click="handleDelete(sign)">
                  Delete
                </button>
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

.back-link {
  color: var(--color-primary);
  text-decoration: none;
}

.eyebrow,
.section-eyebrow {
  margin-top: 1rem;
  margin-bottom: 0.5rem;
  color: var(--color-primary);
  font-size: 0.85rem;
  font-weight: 600;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}

.content {
  display: grid;
  gap: 1.25rem;
  margin-top: 0.5rem;
}

.folder-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.folder-copy h1 {
  color: var(--color-heading);
  font-size: clamp(2rem, 4vw, 2.5rem);
  line-height: 1.05;
}

.slug {
  color: var(--color-text-muted);
}

.folder-actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
}

.badge {
  padding: 0.3rem 0.65rem;
  border-radius: 999px;
  border: 1px solid var(--color-border);
  color: var(--color-heading);
  font-size: 0.85rem;
  text-transform: capitalize;
}

.secondary-link,
.url-link,
.copy-button,
.danger-button {
  padding: 0.6rem 0.9rem;
  border-radius: 0.8rem;
  border: 1px solid var(--color-border);
  background: transparent;
  color: var(--color-heading);
  text-decoration: none;
  cursor: pointer;
}

.meta {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr));
  gap: 1rem;
}

.meta dt,
.sign-meta dt {
  color: var(--color-text-muted);
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.meta dd,
.sign-meta dd {
  margin-top: 0.25rem;
  color: var(--color-heading);
}

.panel {
  padding: 1.25rem;
  border: 1px solid var(--color-border);
  border-radius: 1.25rem;
  background: var(--color-surface-strong);
}

.panel-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.panel h2 {
  color: var(--color-heading);
  font-size: 1.35rem;
}

.section-note,
.muted,
.empty-state,
.selected-file {
  color: var(--color-text-muted);
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

input,
textarea {
  width: 100%;
  padding: 0.85rem 1rem;
  border: 1px solid var(--color-border);
  border-radius: 0.9rem;
  background: rgba(2, 6, 23, 0.35);
  color: var(--color-heading);
}

textarea {
  resize: vertical;
  min-height: 6rem;
}

.actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
  margin-top: 0.25rem;
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

.error-banner {
  margin-top: 1rem;
  padding: 0.9rem 1rem;
  border: 1px solid rgba(251, 113, 133, 0.35);
  border-radius: 0.9rem;
  color: #fecdd3;
  background: rgba(127, 29, 29, 0.24);
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

.sign-card h3 {
  color: var(--color-heading);
  font-size: 1.1rem;
}

.sign-description {
  margin-top: 0.2rem;
  color: var(--color-text-muted);
}

.sign-meta {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(8rem, 1fr));
  gap: 1rem;
}

.sign-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.secondary-link {
  background: rgba(2, 6, 23, 0.12);
}

.copy-button {
  min-width: 6.5rem;
}

.url-link {
  background: rgba(2, 6, 23, 0.12);
}

.danger-button {
  border-color: rgba(251, 113, 133, 0.35);
  color: #fecdd3;
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

  .folder-actions {
    justify-content: flex-start;
  }

  .sign-card {
    grid-template-columns: 1fr;
  }

  .thumb-wrap {
    aspect-ratio: 3 / 1;
  }
}
</style>
