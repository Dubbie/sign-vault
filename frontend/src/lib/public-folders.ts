import type { AxiosError } from 'axios'

import api from '@/lib/api'
import type {
  PaginatedPublicFolderResponse,
  PaginationMeta,
  PublicFolderContentsResponse,
  PublicFolderResponse,
  PublicSign,
  UnlockPublicFolderPayload,
} from '@/types/public-folder'

type PublicFolderApiError = {
  message?: string
  errors?: Record<string, string[]>
}

function getErrorMessage(error: unknown) {
  const axiosError = error as AxiosError<PublicFolderApiError> | undefined
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

export function getPublicFolderErrorMessage(error: unknown) {
  return getErrorMessage(error)
}

export async function getPublicFolder(slug: string): Promise<PublicFolderResponse> {
  const { data } = await api.get<PublicFolderResponse>(`/api/public/folders/${slug}`)
  return data
}

export async function unlockPublicFolder(
  slug: string,
  payload: UnlockPublicFolderPayload,
): Promise<PublicFolderContentsResponse> {
  const { data } = await api.post<PublicFolderContentsResponse>(
    `/api/public/folders/${slug}/unlock`,
    payload,
  )

  return data
}

export async function getPublicFolders(params?: {
  q?: string
  page?: number
  sort?: 'latest' | 'votes'
}): Promise<PaginatedPublicFolderResponse> {
  const { data } = await api.get<PaginatedPublicFolderResponse>('/api/public/folders', { params })
  return data
}

export async function voteFolder(
  slug: string,
): Promise<{ votes_count: number; user_has_voted: boolean }> {
  const { data } = await api.post<{ votes_count: number; user_has_voted: boolean }>(
    `/api/public/folders/${slug}/vote`,
  )
  return data
}

export async function trackFolderPreview(slug: string): Promise<void> {
  try {
    await api.post(`/api/public/folders/${slug}/preview-view`)
  } catch {
    // Tracking is best-effort and must never disrupt the browsing experience.
  }
}

export async function trackSignCopy(slug: string, signId: number): Promise<void> {
  try {
    await api.post(`/api/public/folders/${slug}/signs/${signId}/copy`)
  } catch {
    // Tracking is best-effort and must never disrupt the browsing experience.
  }
}

export async function getPublicFolderSigns(
  slug: string,
  page: number,
  password?: string,
  columnRatio?: number,
  variantId?: number,
): Promise<{ data: PublicSign[]; meta: PaginationMeta }> {
  const payload: Record<string, number | string> = { page }
  if (password) payload.password = password
  if (columnRatio !== undefined) payload.column_ratio = columnRatio
  if (variantId !== undefined) payload.variant_id = variantId

  const { data } = await api.post<{ data: PublicSign[]; meta: PaginationMeta }>(
    `/api/public/folders/${slug}/signs`,
    payload,
  )
  return data
}
