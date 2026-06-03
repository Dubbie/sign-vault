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
            $table->string('sort_key', 500)->nullable()->after('name');
        });

        DB::table('signs')->select('id', 'name')->orderBy('id')->chunkById(500, function ($signs): void {
            foreach ($signs as $sign) {
                DB::table('signs')
                    ->where('id', $sign->id)
                    ->update(['sort_key' => self::naturalSortKey($sign->name)]);
            }
        });

        Schema::table('signs', function (Blueprint $table): void {
            // Replace the name index added in the previous migration
            $table->dropIndex('signs_folder_id_name_index');
            $table->index(['folder_id', 'sort_key'], 'signs_folder_id_sort_key_index');
        });
    }

    public function down(): void
    {
        Schema::table('signs', function (Blueprint $table): void {
            $table->dropIndex('signs_folder_id_sort_key_index');
            $table->index(['folder_id', 'name'], 'signs_folder_id_name_index');
            $table->dropColumn('sort_key');
        });
    }

    private static function naturalSortKey(string $name): string
    {
        $lower = mb_strtolower($name);

        return preg_replace_callback('/\d+/', function (array $matches): string {
            return str_pad($matches[0], 10, '0', STR_PAD_LEFT);
        }, $lower) ?? $lower;
    }
};
