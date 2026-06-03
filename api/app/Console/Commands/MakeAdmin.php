<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'sign-vault:make-admin {discord_id : The Discord ID of the user}';

    protected $description = 'Grant admin privileges to a user by Discord ID';

    public function handle(): int
    {
        $discordId = $this->argument('discord_id');

        $user = User::where('discord_id', $discordId)->first();

        if ($user === null) {
            $this->error("No user found with Discord ID: {$discordId}");

            return self::FAILURE;
        }

        $user->is_admin = true;
        $user->save();

        $this->info("User {$user->discord_username} ({$discordId}) is now an admin.");

        return self::SUCCESS;
    }
}
