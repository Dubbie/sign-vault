<?php

use App\Models\Folder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('folders', function (Blueprint $table): void {
            $table->string('public_slug')->nullable()->unique()->after('slug');
        });

        DB::table('folders')
            ->select(['id', 'name', 'public_slug'])
            ->orderBy('id')
            ->get()
            ->each(function ($folder): void {
                if ($folder->public_slug !== null && $folder->public_slug !== '') {
                    return;
                }

                DB::table('folders')
                    ->where('id', $folder->id)
                    ->update([
                        'public_slug' => Folder::generatePublicSlugFor($folder->name, $folder->id),
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('folders', function (Blueprint $table): void {
            $table->dropUnique(['public_slug']);
            $table->dropColumn('public_slug');
        });
    }
};
