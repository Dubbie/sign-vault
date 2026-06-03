import api from '@/lib/api'
import type {
  AdminUser,
  PaginatedAdminUsers,
} from '@/types/auth'
import type {
  PaginatedPublicFolderResponse,
  PublicFolderContentsResponse,
  PublicFolderListing,
  PublicSign,
} from '@/types/public-folder'

export async function getUsers(page = 1): Promise<PaginatedAdminUsers> {
  const { data } = await api.get<PaginatedAdminUsers>('/api/admin/users', {
    params: { page },
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

export async function getAdminFolderSigns(folderId: number): Promise<{
  folder: { id: number; name: string; slug: string | null; user_id: number }
  signs: PublicSign[]
}> {
  const { data } = await api.get(`/api/admin/folders/${folderId}/signs`)
  return data
}
