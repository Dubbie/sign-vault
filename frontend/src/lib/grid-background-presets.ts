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

const previewOverlayClassesByPreset: Record<GridBackgroundPreset, string> = {
  darkest: 'from-transparent to-[var(--color-darkest)]',
  dark: 'from-transparent to-[var(--color-dark)]',
  medium: 'from-transparent to-[var(--color-medium)]',
}

export function getGridBackgroundSurfaceClasses(preset: GridBackgroundPreset | null): string {
  return surfaceClassesByPreset[preset ?? 'darkest']
}

export function getGridBackgroundPreviewOverlayClasses(
  preset: GridBackgroundPreset | null,
): string {
  return previewOverlayClassesByPreset[preset ?? 'darkest']
}
