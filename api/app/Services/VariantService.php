<?php

namespace App\Services;

use App\Enums\VariantGridBackgroundPreset;
use App\Models\Folder;
use App\Models\Variant;

class VariantService
{
    /**
     * Ensure the folder has a default variant, creating one and backfilling signs if needed.
     *
     * @return bool true when a default was created (backfill was performed)
     */
    public function ensureDefaultExists(Folder $folder): bool
    {
        $existing = $folder->variants()->where('is_default', true)->first();

        if ($existing !== null) {
            return false;
        }

        $default = $folder->variants()->create([
            'name' => 'Default',
            'is_default' => true,
            'sort_order' => 0,
            'grid_background_preset' => VariantGridBackgroundPreset::Darkest->value,
        ]);

        $folder->signs()->whereNull('variant_id')->update(['variant_id' => $default->id]);

        return true;
    }

    /**
     * Promote $newDefault to the folder's default variant, demoting the current one.
     * The old default is renamed from "Default" → "Original" if it still has the generic name.
     */
    public function swapDefault(Folder $folder, Variant $newDefault): void
    {
        if ($newDefault->is_default) {
            return;
        }

        $oldDefault = $folder->defaultVariant;

        if ($oldDefault !== null && $oldDefault->id !== $newDefault->id) {
            $updates = ['is_default' => false];

            if ($oldDefault->name === 'Default') {
                $updates['name'] = 'Original';
            }

            $oldDefault->update($updates);
        }

        $newDefault->update(['is_default' => true]);
    }
}
