<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signs', function (Blueprint $table): void {
            $table->string('thumbnail_storage_key')->nullable()->after('thumbnail_url');
        });
    }

    public function down(): void
    {
        Schema::table('signs', function (Blueprint $table): void {
            $table->dropColumn('thumbnail_storage_key');
        });
    }
};
