<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('folder_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100)->nullable();
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('folder_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variants');
    }
};
