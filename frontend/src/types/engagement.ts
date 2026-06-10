export interface EngagementSummary {
  total_visitors: number
  new_visitors: number
  returning_visitors: number
  folder_opens: number
  sign_copies: number
}

export interface TopFolderEngagement {
  folder_id: number
  folder_name: string | null
  opens: number
}

export interface TopSignEngagement {
  sign_id: number
  sign_name: string | null
  folder_id: number
  folder_name: string | null
  copies: number
}

export interface EngagementTimeseriesPoint {
  date: string
  count: number
}

export interface NewVsReturningPoint {
  date: string
  new: number
  returning: number
}

export interface EngagementStats {
  summary: EngagementSummary
  top_folders: TopFolderEngagement[]
  top_signs: TopSignEngagement[]
  timeseries: {
    daily_active_visitors: EngagementTimeseriesPoint[]
    new_vs_returning: NewVsReturningPoint[]
    folder_opens: EngagementTimeseriesPoint[]
    sign_copies: EngagementTimeseriesPoint[]
  }
}
