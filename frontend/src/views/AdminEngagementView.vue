<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Line } from 'vue-chartjs'
import {
  CategoryScale,
  Chart as ChartJS,
  Filler,
  LinearScale,
  LineElement,
  PointElement,
  Tooltip,
} from 'chart.js'
import type { ScriptableContext } from 'chart.js'

import { getEngagementStats } from '@/lib/admin'
import type { EngagementStats, EngagementTimeseriesPoint } from '@/types/engagement'

import UiErrorBanner from '@/components/ui/UiErrorBanner.vue'
import UiSelect from '@/components/ui/UiSelect.vue'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Filler, Tooltip)

const RANGE_OPTIONS = [
  { value: '7', label: 'Last 7 days' },
  { value: '30', label: 'Last 30 days' },
  { value: '90', label: 'Last 90 days' },
]

const data = ref<EngagementStats | null>(null)
const isLoading = ref(true)
const error = ref<string | null>(null)
const days = ref('30')

async function load() {
  isLoading.value = true
  error.value = null

  try {
    data.value = await getEngagementStats({ days: Number(days.value) })
  } catch {
    error.value = 'Failed to load engagement stats.'
  } finally {
    isLoading.value = false
  }
}

function applyRange() {
  void load()
}

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index' as const, intersect: false },
  scales: {
    x: {
      border: { display: false },
      ticks: { color: '#bbcac0', font: { size: 11 }, maxRotation: 0 },
      grid: { display: false },
    },
    y: {
      border: { display: false },
      beginAtZero: true,
      ticks: { color: '#bbcac0', precision: 0, font: { size: 11 } },
      grid: { color: 'rgba(60, 74, 66, 0.6)' },
    },
  },
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: 'rgba(22, 27, 27, 0.95)',
      borderColor: 'rgba(133, 148, 139, 0.3)',
      borderWidth: 1,
      titleColor: '#dfe3e3',
      bodyColor: '#bbcac0',
      padding: 10,
      cornerRadius: 8,
    },
  },
}

function makeGradient(ctx: CanvasRenderingContext2D, top: number, bottom: number, hex: string) {
  const gradient = ctx.createLinearGradient(0, top, 0, bottom)
  gradient.addColorStop(0, hex + '40')
  gradient.addColorStop(1, hex + '00')
  return gradient
}

function gradientFill(hex: string) {
  return (context: ScriptableContext<'line'>) => {
    const { ctx, chartArea } = context.chart
    if (!chartArea) return 'transparent'
    return makeGradient(ctx, chartArea.top, chartArea.bottom, hex)
  }
}

function lineDataset(label: string, data: number[], hex: string) {
  return {
    label,
    data,
    borderColor: hex,
    backgroundColor: gradientFill(hex),
    borderWidth: 2,
    tension: 0.4,
    fill: true,
    pointRadius: 0,
    pointHoverRadius: 4,
    pointHoverBackgroundColor: hex,
    pointHoverBorderColor: '#0f1414',
    pointHoverBorderWidth: 2,
  }
}

function buildDateLabels(...series: EngagementTimeseriesPoint[][]): string[] {
  const dates = new Set<string>()
  for (const points of series) {
    for (const point of points) dates.add(point.date)
  }
  return [...dates].sort()
}

function alignToLabels(labels: string[], points: EngagementTimeseriesPoint[]): number[] {
  const byDate = new Map(points.map((point) => [point.date, point.count]))
  return labels.map((date) => byDate.get(date) ?? 0)
}

const folderViewsChartData = computed(() => {
  const timeseries = data.value?.timeseries
  if (!timeseries) return null

  const labels = buildDateLabels(timeseries.folder_full_views)

  return {
    labels,
    datasets: [
      lineDataset('Folder opens', alignToLabels(labels, timeseries.folder_full_views), '#00d492'),
    ],
  }
})

const signCopiesChartData = computed(() => {
  const timeseries = data.value?.timeseries
  if (!timeseries) return null

  const labels = buildDateLabels(timeseries.sign_copies)

  return {
    labels,
    datasets: [
      lineDataset('Sign copies', alignToLabels(labels, timeseries.sign_copies), '#45dfa4'),
    ],
  }
})

onMounted(() => {
  void load()
})
</script>

<template>
  <div class="space-y-gutter">
    <div class="flex items-end justify-between gap-4">
      <h1 class="text-headline-xl text-on-surface">Engagement</h1>

      <div class="w-48">
        <UiSelect
          v-model="days"
          name="engagement-range"
          :options="RANGE_OPTIONS"
          @update:model-value="applyRange"
        />
      </div>
    </div>

    <UiErrorBanner v-if="error">
      {{ error }}
    </UiErrorBanner>

    <p v-if="isLoading" class="text-on-surface-variant">Loading...</p>

    <div v-else-if="data" class="space-y-gutter">
      <div class="grid gap-4 sm:grid-cols-3">
        <div class="glass-card rounded-lg p-5">
          <p class="text-label-sm text-on-surface-variant">Folder full views</p>
          <p class="mt-1 text-headline-lg text-on-surface">
            {{ data.summary.folder_full_views.toLocaleString() }}
          </p>
          <p class="mt-1 text-xs text-on-surface-variant/70">Unique visitors who opened a folder</p>
        </div>
        <div class="glass-card rounded-lg p-5">
          <p class="text-label-sm text-on-surface-variant">Returning visitors</p>
          <p class="mt-1 text-headline-lg text-on-surface">
            {{ data.summary.returning_visitors.toLocaleString() }}
          </p>
          <p class="mt-1 text-xs text-on-surface-variant/70">Unique visitors active on 2+ days</p>
        </div>
        <div class="glass-card rounded-lg p-5">
          <p class="text-label-sm text-on-surface-variant">Sign copies</p>
          <p class="mt-1 text-headline-lg text-on-surface">
            {{ data.summary.sign_copies.toLocaleString() }}
          </p>
          <p class="mt-1 text-xs text-on-surface-variant/70">
            Unique visitors who copied a sign URL
          </p>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <div class="glass-card rounded-lg p-5">
          <h2 class="text-headline-md text-on-surface">Folder opens over time</h2>
          <div class="mt-4 h-64">
            <Line
              v-if="folderViewsChartData"
              :data="folderViewsChartData"
              :options="chartOptions"
            />
          </div>
        </div>
        <div class="glass-card rounded-lg p-5">
          <h2 class="text-headline-md text-on-surface">Sign copies over time</h2>
          <div class="mt-4 h-64">
            <Line v-if="signCopiesChartData" :data="signCopiesChartData" :options="chartOptions" />
          </div>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <div class="glass-card overflow-hidden rounded-lg">
          <div class="px-5 py-4">
            <h2 class="text-headline-md text-on-surface">Top folders by views</h2>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full">
              <thead class="bg-surface-container-low">
                <tr class="text-left">
                  <th class="px-4 py-3 text-xs text-on-surface-variant">Folder</th>
                  <th class="px-4 py-3 text-xs text-on-surface-variant">Views</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="folder in data.top_folders"
                  :key="folder.folder_id"
                  class="border-t border-outline-variant/60"
                >
                  <td class="px-4 py-3 text-sm text-on-surface">
                    <span
                      class="block max-w-[16rem] truncate"
                      :title="folder.folder_name ?? undefined"
                    >
                      {{ folder.folder_name ?? `Folder #${folder.folder_id}` }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm text-on-surface-variant">{{ folder.full_views }}</td>
                </tr>
                <tr v-if="data.top_folders.length === 0">
                  <td colspan="2" class="px-4 py-6 text-center text-sm text-on-surface-variant">
                    No folder views recorded in this range.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="glass-card overflow-hidden rounded-lg">
          <div class="px-5 py-4">
            <h2 class="text-headline-md text-on-surface">Top signs by copies</h2>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full">
              <thead class="bg-surface-container-low">
                <tr class="text-left">
                  <th class="px-4 py-3 text-xs text-on-surface-variant">Sign</th>
                  <th class="px-4 py-3 text-xs text-on-surface-variant">Folder</th>
                  <th class="px-4 py-3 text-xs text-on-surface-variant">Copies</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="sign in data.top_signs"
                  :key="sign.sign_id"
                  class="border-t border-outline-variant/60"
                >
                  <td class="px-4 py-3 text-sm text-on-surface">
                    <span class="block max-w-[12rem] truncate" :title="sign.sign_name ?? undefined">
                      {{ sign.sign_name ?? `Sign #${sign.sign_id}` }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm text-on-surface-variant">
                    <span
                      class="block max-w-[12rem] truncate"
                      :title="sign.folder_name ?? undefined"
                    >
                      {{ sign.folder_name ?? `Folder #${sign.folder_id}` }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm text-on-surface-variant">{{ sign.copies }}</td>
                </tr>
                <tr v-if="data.top_signs.length === 0">
                  <td colspan="3" class="px-4 py-6 text-center text-sm text-on-surface-variant">
                    No sign copies recorded in this range.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
