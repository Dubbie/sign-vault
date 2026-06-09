<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signs', function (Blueprint $table): void {
            $table->index(
                ['folder_id', 'user_id', 'name', 'variant_id', 'width', 'height'],
                'signs_find_existing_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('signs', function (Blueprint $table): void {
            $table->dropIndex('signs_find_existing_index');
        });
    }
};
