<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'display_name',
    'avatar_url',
    'avatar_storage_key',
    'email',
    'is_admin',
    'banned_at',
    'ban_reason',
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'is_admin' => 'boolean',
            'banned_at' => 'datetime',
        ];
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    public function scopeNotBanned(Builder $query): void
    {
        $query->whereNull('banned_at');
    }

    public function oauthProviders(): HasMany
    {
        return $this->hasMany(OauthProvider::class);
    }

    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    public function signs(): HasMany
    {
        return $this->hasMany(Sign::class);
    }
}
