import type { AxiosError } from 'axios'

import api from '@/lib/api'
import type {
  CreateFolderPayload,
  Folder,
  UpdateFolderPayload,
} from '@/types/folder'

type FolderApiError = {
  message?: string
  errors?: Record<string, string[]>
}

function getErrorMessage(error: unknown) {
  const axiosError = error as AxiosError<FolderApiError> | undefined
  const responseData = axiosError?.response?.data

  if (responseData?.message) {
    return responseData.message
  }

  if (responseData?.errors) {
    const firstError = Object.values(responseData.errors).flat()[0]

    if (firstError) {
      return firstError
    }
  }

  return 'Something went wrong.'
}

export function getFolderErrorMessage(error: unknown) {
  return getErrorMessage(error)
}

export async function getFolders(): Promise<Folder[]> {
  const { data } = await api.get<Folder[]>('/api/folders')
  return data
}

export async function getFolder(id: number): Promise<Folder> {
  const { data } = await api.get<Folder>(`/api/folders/${id}`)
  return data
}

export async function createFolder(payload: CreateFolderPayload): Promise<Folder> {
  const { data } = await api.post<Folder>('/api/folders', payload)
  return data
}

export async function updateFolder(id: number, payload: UpdateFolderPayload): Promise<Folder> {
  const { data } = await api.patch<Folder>(`/api/folders/${id}`, payload)
  return data
}

export async function deleteFolder(id: number): Promise<void> {
  await api.delete(`/api/folders/${id}`)
}
