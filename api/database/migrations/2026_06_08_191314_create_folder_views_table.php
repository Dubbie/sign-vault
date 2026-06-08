<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folder_views', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('folder_id')->constrained()->cascadeOnDelete();
            $table->string('ip_hash', 64);
            $table->string('view_type', 20);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamps();

            $table->unique(['folder_id', 'ip_hash', 'view_type']);
            $table->index('first_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folder_views');
    }
};
