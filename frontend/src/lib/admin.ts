import api from '@/lib/api'
import type { PaginatedAdminUsers } from '@/types/auth'
import type { PaginatedPublicFolderResponse, PublicSign } from '@/types/public-folder'
import type { PaginatedActivityLogs } from '@/types/activity-log'

export async function getUsers(page = 1, q?: string): Promise<PaginatedAdminUsers> {
  const { data } = await api.get<PaginatedAdminUsers>('/api/admin/users', {
    params: { page, ...(q ? { q } : {}) },
  })
  return data
}

export async function banUser(userId: number, reason: string): Promise<void> {
  await api.post(`/api/admin/users/${userId}/ban`, { reason })
}

export async function unbanUser(userId: number): Promise<void> {
  await api.post(`/api/admin/users/${userId}/unban`)
}

export async function getAllFolders(params?: {
  q?: string
  page?: number
}): Promise<PaginatedPublicFolderResponse> {
  const { data } = await api.get<PaginatedPublicFolderResponse>('/api/admin/folders', { params })
  return data
}

export async function deleteAdminFolder(folderId: number): Promise<void> {
  await api.delete(`/api/admin/folders/${folderId}`)
}

export async function deleteAdminSign(signId: number): Promise<void> {
  await api.delete(`/api/admin/signs/${signId}`)
}

export async function getAdminFolderSigns(folderId: number): Promise<{
  folder: { id: number; name: string; slug: string | null; user_id: number }
  signs: PublicSign[]
}> {
  const { data } = await api.get(`/api/admin/folders/${folderId}/signs`)
  return data
}

export async function getActivityLogs(params?: {
  event?: string
  actor_id?: number
  subject_user_id?: number
  date_from?: string
  date_to?: string
  page?: number
  per_page?: number
}): Promise<PaginatedActivityLogs> {
  const { data } = await api.get<PaginatedActivityLogs>('/api/admin/logs', { params })
  return data
}
