<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('folder_views', function (Blueprint $table): void {
            $table->index(
                ['view_type', 'first_seen_at', 'folder_id'],
                'folder_views_reporting_index'
            );
        });

        Schema::table('sign_copies', function (Blueprint $table): void {
            $table->index(
                ['first_seen_at', 'folder_id', 'sign_id'],
                'sign_copies_reporting_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('folder_views', function (Blueprint $table): void {
            $table->dropIndex('folder_views_reporting_index');
        });

        Schema::table('sign_copies', function (Blueprint $table): void {
            $table->dropIndex('sign_copies_reporting_index');
        });
    }
};
