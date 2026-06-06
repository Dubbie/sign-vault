<?php

namespace App\Models;

use Database\Factories\OauthProviderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'provider',
    'provider_user_id',
    'username',
    'display_name',
    'avatar_url',
    'email',
])]
class OauthProvider extends Model
{
    /** @use HasFactory<OauthProviderFactory> */
    use HasFactory;

    public const DISCORD = 'discord';

    public const TRACKMANIA = 'trackmania';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
