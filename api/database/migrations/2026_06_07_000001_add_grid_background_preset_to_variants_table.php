<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('variants', function (Blueprint $table): void {
            $table->string('grid_background_preset', 32)
                ->nullable()
                ->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('variants', function (Blueprint $table): void {
            $table->dropColumn('grid_background_preset');
        });
    }
};
