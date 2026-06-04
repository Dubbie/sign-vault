<?php

use App\Models\Folder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signs', function (Blueprint $table): void {
            $table->foreignId('variant_id')
                ->nullable()
                ->after('folder_id')
                ->constrained('variants')
                ->nullOnDelete();
        });

        DB::table('folders')->orderBy('id')->chunkById(500, function ($folders): void {
            foreach ($folders as $folder) {
                $defaultVariantId = DB::table('variants')->insertGetId([
                    'folder_id' => $folder->id,
                    'name' => 'Default',
                    'is_default' => true,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('signs')
                    ->where('folder_id', $folder->id)
                    ->update(['variant_id' => $defaultVariantId]);
            }
        });

        Schema::table('signs', function (Blueprint $table): void {
            $table->index(['folder_id', 'variant_id'], 'signs_folder_id_variant_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('signs', function (Blueprint $table): void {
            $table->dropIndex('signs_folder_id_variant_id_index');
            $table->dropForeign('signs_variant_id_foreign');
            $table->dropColumn('variant_id');
        });
    }
};
