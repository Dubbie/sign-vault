<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step A: create the oauth_providers table
        Schema::create('oauth_providers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_user_id');
            $table->string('username');
            $table->string('display_name')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_user_id']);
            $table->index('user_id');
        });

        // Step B: add generic identity columns to users
        Schema::table('users', function (Blueprint $table): void {
            $table->string('display_name')->nullable()->after('email');
            $table->string('avatar_url')->nullable()->after('display_name');
        });

        // Step C: backfill existing Discord users in chunks
        DB::table('users')
            ->whereNotNull('discord_id')
            ->orderBy('id')
            ->chunk(500, function ($users): void {
                foreach ($users as $user) {
                    DB::table('oauth_providers')->insertOrIgnore([
                        'user_id'          => $user->id,
                        'provider'         => 'discord',
                        'provider_user_id' => $user->discord_id,
                        'username'         => $user->discord_username,
                        'display_name'     => $user->discord_global_name,
                        'avatar_url'       => $user->discord_avatar,
                        'email'            => $user->email,
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]);

                    DB::table('users')->where('id', $user->id)->update([
                        'display_name' => $user->discord_global_name ?? $user->discord_username,
                        'avatar_url'   => $user->discord_avatar,
                    ]);
                }
            });

        // Step D: drop old Discord-specific columns
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['discord_id']);
            $table->dropColumn([
                'discord_id',
                'discord_username',
                'discord_global_name',
                'discord_avatar',
            ]);
        });
    }

    public function down(): void
    {
        // Restore Discord columns
        Schema::table('users', function (Blueprint $table): void {
            $table->string('discord_id')->nullable()->after('email');
            $table->string('discord_username')->nullable()->after('discord_id');
            $table->string('discord_global_name')->nullable()->after('discord_username');
            $table->string('discord_avatar')->nullable()->after('discord_global_name');
        });

        // Restore Discord data from oauth_providers
        DB::table('oauth_providers')
            ->where('provider', 'discord')
            ->orderBy('user_id')
            ->chunk(500, function ($providers): void {
                foreach ($providers as $provider) {
                    DB::table('users')->where('id', $provider->user_id)->update([
                        'discord_id'          => $provider->provider_user_id,
                        'discord_username'     => $provider->username,
                        'discord_global_name'  => $provider->display_name,
                        'discord_avatar'       => $provider->avatar_url,
                    ]);
                }
            });

        // Add back the unique constraint
        Schema::table('users', function (Blueprint $table): void {
            $table->unique('discord_id');
            $table->dropColumn(['display_name', 'avatar_url']);
        });

        Schema::dropIfExists('oauth_providers');
    }
};
