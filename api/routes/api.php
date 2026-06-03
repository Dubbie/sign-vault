<?php

use App\Http\Controllers\Admin\AdminBrowseController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Auth\DiscordAuthController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\PublicFolderController;
use App\Http\Controllers\SignController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/discord/redirect', [DiscordAuthController::class, 'redirect']);
Route::post('/auth/discord/callback', [DiscordAuthController::class, 'callback']);
Route::get('/public/folders', [PublicFolderController::class, 'index']);
Route::get('/public/folders/{slug}', [PublicFolderController::class, 'show']);
Route::post('/public/folders/{slug}/unlock', [PublicFolderController::class, 'unlock'])
    ->middleware('throttle:folder-unlock');

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
    Route::get('/folders/{folder}/signs', [SignController::class, 'index']);
    Route::post('/folders/{folder}/signs', [SignController::class, 'store']);
    Route::get('/signs/{sign}', [SignController::class, 'show']);
    Route::patch('/signs/move', [SignController::class, 'move']);
    Route::delete('/signs', [SignController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function (): void {
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::post('/users/{user}/ban', [AdminUserController::class, 'ban']);
    Route::post('/users/{user}/unban', [AdminUserController::class, 'unban']);
    Route::get('/folders', [AdminBrowseController::class, 'folders']);
    Route::get('/folders/{folder}/signs', [AdminBrowseController::class, 'folderSigns']);
});
