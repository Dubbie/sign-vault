<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folder_authors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('folder_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('source_url', 2048)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['folder_id', 'sort_order']);
        });

        if (! Schema::hasColumns('folders', ['attribution_name', 'attribution_source_url'])) {
            return;
        }

        DB::table('folders')
            ->select(['id', 'attribution_name', 'attribution_source_url', 'created_at', 'updated_at'])
            ->whereNotNull('attribution_name')
            ->where('attribution_name', '!=', '')
            ->orderBy('id')
            ->chunkById(500, function ($folders): void {
                $rows = [];

                foreach ($folders as $folder) {
                    $rows[] = [
                        'folder_id' => $folder->id,
                        'name' => $folder->attribution_name,
                        'source_url' => $folder->attribution_source_url,
                        'sort_order' => 0,
                        'created_at' => $folder->created_at,
                        'updated_at' => $folder->updated_at,
                    ];
                }

                if ($rows !== []) {
                    DB::table('folder_authors')->insert($rows);
                }
            });

        Schema::table('folders', function (Blueprint $table): void {
            $table->dropColumn(['attribution_name', 'attribution_source_url']);
        });
    }
};
