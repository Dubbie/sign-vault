import api from '@/lib/api'

export type AppStats = {
  total_users: number
  total_signs: number
  cdn_latency_ms: number | null
  uptime_percentage: number | null
  server_uptime_seconds: number | null
  is_up: boolean
}

export async function getAppStats(): Promise<AppStats> {
  const { data } = await api.get<AppStats>('/api/stats')
  return data
}
