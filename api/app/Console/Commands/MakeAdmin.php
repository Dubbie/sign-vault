<?php

namespace App\Console\Commands;

use App\Models\OauthProvider;
use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'sign-vault:make-admin
        {id : The user ID, or a provider-qualified ID like discord:123456789 or trackmania:uuid}';

    protected $description = 'Grant admin privileges to a user by ID or provider ID';

    public function handle(): int
    {
        $input = (string) $this->argument('id');

        $user = $this->resolveUser($input);

        if ($user === null) {
            $this->error("No user found for: {$input}");

            return self::FAILURE;
        }

        $user->is_admin = true;
        $user->save();

        $this->info("User \"{$user->display_name}\" (id={$user->id}) is now an admin.");

        return self::SUCCESS;
    }

    private function resolveUser(string $input): ?User
    {
        if (str_contains($input, ':')) {
            [$provider, $providerId] = explode(':', $input, 2);

            return OauthProvider::where('provider', $provider)
                ->where('provider_user_id', $providerId)
                ->first()
                ?->user;
        }

        return User::find((int) $input);
    }
}
