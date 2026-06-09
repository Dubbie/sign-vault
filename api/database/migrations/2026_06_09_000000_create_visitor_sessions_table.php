<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_sessions', function (Blueprint $table): void {
            $table->id();
            $table->string('ip_hash', 64);
            $table->date('session_date');
            $table->timestamps();
            $table->unique(['ip_hash', 'session_date']);
            $table->index('session_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_sessions');
    }
};
