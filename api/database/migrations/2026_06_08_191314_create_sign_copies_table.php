<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sign_copies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('folder_id')->constrained()->cascadeOnDelete();
            $table->string('ip_hash', 64);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamps();

            $table->unique(['sign_id', 'ip_hash']);
            $table->index('first_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sign_copies');
    }
};
