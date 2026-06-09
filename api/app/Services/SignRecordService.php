<?php

namespace App\Services;

use App\Models\Sign;

class SignRecordService
{
    public function nameForOriginal(string $originalName): string
    {
        $original = pathinfo($originalName, PATHINFO_FILENAME);
        $derived = trim((string) $original);

        return $derived !== '' ? $derived : 'sign';
    }

    public function findExisting(
        int $userId,
        int $folderId,
        ?int $variantId,
        string $name,
        ?int $width,
        ?int $height
    ): ?Sign {
        $query = Sign::query()
            ->where('user_id', $userId)
            ->where('folder_id', $folderId)
            ->where('name', $name);

        if ($variantId !== null) {
            $query->where('variant_id', $variantId);
        } else {
            $query->whereNull('variant_id');
        }

        if ($width !== null && $height !== null) {
            $query->where('width', $width)->where('height', $height);
        } else {
            $query->whereNull('width')->whereNull('height');
        }

        return $query->first();
    }

    public function columnRatioFor(?int $width, ?int $height): int
    {
        if (! $width || ! $height) {
            return 1;
        }

        $ratio = $width / $height;
        $columns = [6, 4, 2, 1];
        $closest = $columns[0];
        $minDiff = abs($ratio - $closest);

        foreach ($columns as $columnRatio) {
            $diff = abs($ratio - $columnRatio);

            if ($diff < $minDiff) {
                $minDiff = $diff;
                $closest = $columnRatio;
            }
        }

        return $closest;
    }

    public function naturalSortKey(string $name): string
    {
        $lower = mb_strtolower($name);

        return preg_replace_callback('/\d+/', function (array $matches): string {
            return str_pad($matches[0], 10, '0', STR_PAD_LEFT);
        }, $lower) ?? $lower;
    }
}
