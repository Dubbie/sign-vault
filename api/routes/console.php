<?php

use App\Console\Commands\MakeAdmin;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sign-vault:make-admin {discord_id}', function () {
    $this->call(MakeAdmin::class, [
        'discord_id' => $this->argument('discord_id'),
    ]);
})->purpose('Grant admin privileges to a user by Discord ID');
