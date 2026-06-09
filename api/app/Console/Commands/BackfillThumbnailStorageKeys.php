<?php

namespace App\Console\Commands;

use App\Models\Sign;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackfillThumbnailStorageKeys extends Command
{
    protected $signature = 'signs:backfill-thumbnail-storage-keys {--dry-run : Preview changes without writing to the database}';

    protected $description = 'Derive and store thumbnail_storage_key for signs that have a thumbnail_url but no storage key';

    public function handle(): int
    {
        $total = Sign::whereNotNull('thumbnail_url')->whereNull('thumbnail_storage_key')->count();

        if ($total === 0) {
            $this->info('Nothing to backfill.');

            return self::SUCCESS;
        }

        $dryRun = $this->option('dry-run');
        $updated = 0;
        $skipped = 0;

        // Cache base URLs per disk so config isn't re-read for every row.
        $baseUrlByDisk = [];

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Sign::whereNotNull('thumbnail_url')
            ->whereNull('thumbnail_storage_key')
            ->select(['id', 'storage_disk', 'thumbnail_url'])
            ->chunkById(100, function ($signs) use ($dryRun, &$baseUrlByDisk, &$updated, &$skipped, $bar): void {
                $rows = [];

                foreach ($signs as $sign) {
                    $disk = $sign->storage_disk;

                    if (! isset($baseUrlByDisk[$disk])) {
                        $baseUrlByDisk[$disk] = rtrim((string) config("filesystems.disks.{$disk}.url"), '/');
                    }

                    $baseUrl = $baseUrlByDisk[$disk];

                    if ($baseUrl === '' || ! str_starts_with((string) $sign->thumbnail_url, $baseUrl.'/')) {
                        $this->newLine();
                        $this->warn("Sign #{$sign->id}: thumbnail_url does not match disk base URL — skipping.");
                        Log::warning('signs:backfill-thumbnail-storage-keys skipped sign', [
                            'sign_id' => $sign->id,
                            'thumbnail_url' => $sign->thumbnail_url,
                            'disk' => $disk,
                            'base_url' => $baseUrl,
                        ]);
                        $skipped++;
                        $bar->advance();

                        continue;
                    }

                    $rows[] = [
                        'id' => $sign->id,
                        'thumbnail_storage_key' => substr((string) $sign->thumbnail_url, strlen($baseUrl) + 1),
                    ];

                    $updated++;
                    $bar->advance();
                }

                if (! $dryRun && $rows !== []) {
                    $this->updateThumbnailStorageKeys($rows);
                }
            });

        $bar->finish();
        $this->newLine();

        $verb = $dryRun ? 'Would update' : 'Updated';
        $this->info("{$verb}: {$updated}, skipped: {$skipped}.");

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array{id: int, thumbnail_storage_key: string}>  $rows
     */
    private function updateThumbnailStorageKeys(array $rows): void
    {
        $timestamp = now();
        $caseClauses = [];
        $caseBindings = [];
        $idBindings = [];

        foreach ($rows as $row) {
            $caseClauses[] = 'WHEN ? THEN ?';
            $caseBindings[] = $row['id'];
            $caseBindings[] = $row['thumbnail_storage_key'];
            $idBindings[] = $row['id'];
        }

        $inClause = implode(', ', array_fill(0, count($idBindings), '?'));
        $caseSql = implode(' ', $caseClauses);

        DB::update(
            "UPDATE signs
            SET thumbnail_storage_key = CASE id {$caseSql} END,
                updated_at = ?
            WHERE id IN ({$inClause})",
            [...$caseBindings, $timestamp, ...$idBindings],
        );
    }
}
