<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signs', function (Blueprint $table): void {
            $table->unsignedTinyInteger('column_ratio')->default(1)->after('height');
        });

        DB::table('signs')->select('id', 'width', 'height')->orderBy('id')->chunkById(500, function ($signs): void {
            foreach ($signs as $sign) {
                DB::table('signs')
                    ->where('id', $sign->id)
                    ->update(['column_ratio' => self::computeColumnRatio($sign->width, $sign->height)]);
            }
        });

        Schema::table('signs', function (Blueprint $table): void {
            $table->index(['folder_id', 'name'], 'signs_folder_id_name_index');
        });
    }

    public function down(): void
    {
        Schema::table('signs', function (Blueprint $table): void {
            $table->dropIndex('signs_folder_id_name_index');
            $table->dropColumn('column_ratio');
        });
    }

    private static function computeColumnRatio(?int $width, ?int $height): int
    {
        if (! $width || ! $height) {
            return 1;
        }

        $ratio = $width / $height;
        $columns = [6, 4, 2, 1];
        $closest = $columns[0];
        $minDiff = abs($ratio - $closest);

        foreach ($columns as $col) {
            $diff = abs($ratio - $col);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $closest = $col;
            }
        }

        return $closest;
    }
};
