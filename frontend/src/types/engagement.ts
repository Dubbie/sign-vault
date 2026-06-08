export interface EngagementSummary {
  folder_full_views: number
  folder_previews: number
  sign_copies: number
}

export interface TopFolderEngagement {
  folder_id: number
  folder_name: string | null
  full_views: number
  previews: number
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

export interface EngagementStats {
  summary: EngagementSummary
  top_folders: TopFolderEngagement[]
  top_signs: TopSignEngagement[]
  timeseries: {
    folder_full_views: EngagementTimeseriesPoint[]
    folder_previews: EngagementTimeseriesPoint[]
    sign_copies: EngagementTimeseriesPoint[]
  }
}
