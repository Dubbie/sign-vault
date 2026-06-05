<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('folders', function (Blueprint $table): void {
            $table->string('attribution_name')->nullable()->after('password_hash');
            $table->string('attribution_source_url')->nullable()->after('attribution_name');
        });
    }

    public function down(): void
    {
        Schema::table('folders', function (Blueprint $table): void {
            $table->dropColumn(['attribution_name', 'attribution_source_url']);
        });
    }
};
