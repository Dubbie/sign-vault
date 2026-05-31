<?php

use App\Http\Controllers\Auth\DiscordAuthController;
use App\Http\Controllers\FolderController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/discord/redirect', [DiscordAuthController::class, 'redirect']);
Route::post('/auth/discord/callback', [DiscordAuthController::class, 'callback']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [DiscordAuthController::class, 'logout']);
    Route::get('/me', [DiscordAuthController::class, 'me']);
    Route::apiResource('folders', FolderController::class)->only([
        'index',
        'store',
        'show',
        'update',
        'destroy',
    ]);
});
