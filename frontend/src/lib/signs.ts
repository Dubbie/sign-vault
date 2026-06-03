import type { AxiosError } from 'axios'

import api from '@/lib/api'
import type { CreateSignPayload, Sign } from '@/types/sign'

type SignApiError = {
  message?: string
  errors?: Record<string, string[]>
}

function getErrorMessage(error: unknown) {
  const axiosError = error as AxiosError<SignApiError> | undefined
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

export function getSignErrorMessage(error: unknown) {
  return getErrorMessage(error)
}

export async function getFolderSigns(folderId: number): Promise<Sign[]> {
  const { data } = await api.get<Sign[]>(`/api/folders/${folderId}/signs`)
  return data
}

export async function getSign(id: number): Promise<Sign> {
  const { data } = await api.get<Sign>(`/api/signs/${id}`)
  return data
}

export async function createSigns(folderId: number, payload: CreateSignPayload): Promise<Sign[]> {
  const formData = new FormData()
  payload.files.forEach((file) => {
    formData.append('files[]', file)
  })

  const { data } = await api.post<{ signs: Sign[] }>(`/api/folders/${folderId}/signs`, formData)
  return data.signs
}

export async function deleteSigns(ids: number[]): Promise<void> {
  await api.delete('/api/signs', { data: { ids } })
}

export async function moveSigns(signIds: number[], targetFolderId: number): Promise<void> {
  await api.patch('/api/signs/move', {
    ids: signIds,
    folder_id: targetFolderId,
  })
}
