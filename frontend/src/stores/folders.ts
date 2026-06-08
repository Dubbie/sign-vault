import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

import {
  createFolder as createFolderRequest,
  deleteFolder as deleteFolderRequest,
  getFolder as getFolderRequest,
  getFolderErrorMessage,
  getFolders as getFoldersRequest,
  updateFolder as updateFolderRequest,
} from '@/lib/folders'
import { getErrorStatus } from '@/lib/http-errors'
import type { CreateFolderPayload, Folder, UpdateFolderPayload } from '@/types/folder'

export const useFoldersStore = defineStore('folders', () => {
  const folders = ref<Folder[]>([])
  const currentFolder = ref<Folder | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const errorStatus = ref<number | null>(null)

  const folderCount = computed(() => folders.value.length)

  function clearCurrentFolder() {
    currentFolder.value = null
  }

  function clearError() {
    error.value = null
    errorStatus.value = null
  }

  function setErrorFromUnknown(exception: unknown) {
    error.value = getFolderErrorMessage(exception)
    errorStatus.value = getErrorStatus(exception) ?? null
  }

  function upsertFolder(folder: Folder) {
    const index = folders.value.findIndex((item) => item.id === folder.id)

    if (index === -1) {
      folders.value = [folder, ...folders.value]
      return
    }

    folders.value = folders.value.map((item) => (item.id === folder.id ? folder : item))
  }

  function removeFolder(id: number) {
    folders.value = folders.value.filter((folder) => folder.id !== id)

    if (currentFolder.value?.id === id) {
      currentFolder.value = null
    }
  }

  async function fetchFolders() {
    isLoading.value = true
    clearError()

    try {
      folders.value = await getFoldersRequest()
      return folders.value
    } catch (exception) {
      setErrorFromUnknown(exception)
      return []
    } finally {
      isLoading.value = false
    }
  }

  async function fetchFolder(id: number) {
    isLoading.value = true
    clearError()

    try {
      const folder = await getFolderRequest(id)
      currentFolder.value = folder
      upsertFolder(folder)
      return folder
    } catch (exception) {
      setErrorFromUnknown(exception)
      currentFolder.value = null
      return null
    } finally {
      isLoading.value = false
    }
  }

  async function createFolder(payload: CreateFolderPayload) {
    isLoading.value = true
    clearError()

    try {
      const folder = await createFolderRequest(payload)
      upsertFolder(folder)
      currentFolder.value = folder
      return folder
    } catch (exception) {
      setErrorFromUnknown(exception)
      return null
    } finally {
      isLoading.value = false
    }
  }

  async function updateFolder(id: number, payload: UpdateFolderPayload) {
    isLoading.value = true
    clearError()

    try {
      const folder = await updateFolderRequest(id, payload)
      upsertFolder(folder)
      currentFolder.value = folder
      return folder
    } catch (exception) {
      setErrorFromUnknown(exception)
      return null
    } finally {
      isLoading.value = false
    }
  }

  async function deleteFolder(id: number) {
    isLoading.value = true
    clearError()

    try {
      await deleteFolderRequest(id)
      removeFolder(id)
    } catch (exception) {
      setErrorFromUnknown(exception)
    } finally {
      isLoading.value = false
    }
  }

  return {
    folders,
    currentFolder,
    isLoading,
    error,
    errorStatus,
    folderCount,
    fetchFolders,
    fetchFolder,
    createFolder,
    updateFolder,
    deleteFolder,
    clearCurrentFolder,
    clearError,
  }
})
