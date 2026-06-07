import type { GridBackgroundPreset } from '@/types/grid-background'

export const gridBackgroundPresetOptions = [
  { value: 'darkest', label: 'Darkest' },
  { value: 'dark', label: 'Dark' },
  { value: 'medium', label: 'Medium' },
]

const surfaceClassesByPreset: Record<GridBackgroundPreset, string> = {
  darkest: 'rounded-lg bg-[var(--color-darkest)] p-2 sm:p-2',
  dark: 'rounded-lg bg-[var(--color-dark)] p-2 sm:p-2',
  medium: 'rounded-lg bg-[var(--color-medium)] p-2 sm:p-2',
}

export function getGridBackgroundSurfaceClasses(preset: GridBackgroundPreset | null): string {
  return surfaceClassesByPreset[preset ?? 'darkest']
}
