<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table): void {
            $table->uuid('upload_session_id')->nullable()->after('ip_address');
            $table->index(
                ['event', 'actor_id', 'subject_folder_id', 'upload_session_id'],
                'activity_logs_upload_session_lookup_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table): void {
            $table->dropIndex('activity_logs_upload_session_lookup_index');
            $table->dropColumn('upload_session_id');
        });
    }
};
