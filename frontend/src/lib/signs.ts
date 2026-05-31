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

export async function createSign(folderId: number, payload: CreateSignPayload): Promise<Sign> {
  const formData = new FormData()
  formData.append('file', payload.file)

  if (payload.name) {
    formData.append('name', payload.name)
  }

  if (payload.description) {
    formData.append('description', payload.description)
  }

  const { data } = await api.post<Sign>(`/api/folders/${folderId}/signs`, formData)
  return data
}

export async function deleteSign(id: number): Promise<void> {
  await api.delete(`/api/signs/${id}`)
}
