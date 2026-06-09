<?php

namespace App\Services;

use App\Models\Folder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FolderPreviewService
{
    public const ASPECT_1_1_MAX = 1.5;

    public const ASPECT_2_1_MAX = 3;

    public const ASPECT_4_1_MAX = 5;

    public function loadPreviewSigns(Collection $folders): void
    {
        if ($folders->isEmpty()) {
            return;
        }

        $foldersByVariant = $folders
            ->filter(fn (Folder $folder): bool => $folder->defaultVariant !== null)
            ->map(fn (Folder $folder): array => [
                'folder_id' => $folder->id,
                'variant_id' => $folder->defaultVariant->id,
            ])
            ->values();

        if ($foldersByVariant->isEmpty()) {
            foreach ($folders as $folder) {
                $folder->setRelation('previewSigns', collect());
            }

            return;
        }

        $t11 = self::ASPECT_1_1_MAX;
        $t21 = self::ASPECT_2_1_MAX;
        $t41 = self::ASPECT_4_1_MAX;

        $aspectBucket = <<<SQL
CASE
    WHEN width IS NULL OR height IS NULL OR height = 0 THEN 'unknown'
    WHEN width / height < {$t11} THEN '1:1'
    WHEN width / height < {$t21} THEN '2:1'
    WHEN width / height < {$t41} THEN '4:1'
    ELSE 'wide'
END
SQL;

        $rankedSigns = DB::table('signs')
            ->select([
                'id',
                'name',
                'public_url',
                'thumbnail_url',
                'thumbnail_status',
                'mime_type',
                'width',
                'height',
                'column_ratio',
                'folder_id',
                'variant_id',
            ])
            ->selectRaw("{$aspectBucket} as aspect_bucket")
            ->selectRaw("ROW_NUMBER() OVER (PARTITION BY folder_id, {$aspectBucket} ORDER BY id) as bucket_rank")
            ->whereRaw(
                '(folder_id, variant_id) IN ('.$foldersByVariant->map(fn (): string => '(?, ?)')->join(', ').')',
                $foldersByVariant->flatMap(fn ($f): array => [$f['folder_id'], $f['variant_id']])->toArray()
            );

        $previewSigns = DB::query()
            ->fromSub($rankedSigns, 'ranked_signs')
            ->where('bucket_rank', '<=', 6)
            ->orderBy('folder_id')
            ->orderByRaw("
                CASE aspect_bucket
                    WHEN '1:1' THEN 1
                    WHEN '2:1' THEN 2
                    WHEN '4:1' THEN 3
                    WHEN 'wide' THEN 4
                    ELSE 5
                END
            ")
            ->orderBy('id')
            ->get()
            ->groupBy('folder_id');

        foreach ($folders as $folder) {
            $folder->setRelation('previewSigns', $previewSigns->get($folder->id, collect()));
        }
    }
}
