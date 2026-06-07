<?php

namespace App\Models;

use App\Enums\FolderVisibility;
use App\Enums\VariantGridBackgroundPreset;
use Database\Factories\FolderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'slug',
    'public_slug',
    'visibility',
    'password_hash',
    'attribution_name',
    'attribution_source_url',
])]
class Folder extends Model
{
    /** @use HasFactory<FolderFactory> */
    use HasFactory;

    protected $hidden = [
        'password_hash',
        'public_slug',
    ];

    protected function casts(): array
    {
        return [
            'visibility' => FolderVisibility::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function signs(): HasMany
    {
        return $this->hasMany(Sign::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }

    public function defaultVariant(): HasOne
    {
        return $this->hasOne(Variant::class)->where('is_default', true);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(FolderVote::class);
    }

    protected static function booted(): void
    {
        static::created(function (Folder $folder): void {
            $folder->variants()->create([
                'name' => 'Default',
                'is_default' => true,
                'sort_order' => 0,
                'grid_background_preset' => VariantGridBackgroundPreset::Darkest->value,
            ]);
        });
    }

    public static function generatePublicSlugFor(string $name, ?int $ignoreFolderId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'folder';
        $query = static::query()
            ->where(function ($query) use ($baseSlug): void {
                $query->where('public_slug', $baseSlug)
                    ->orWhere('public_slug', 'like', $baseSlug.'-%');
            });

        if ($ignoreFolderId !== null) {
            $query->whereKeyNot($ignoreFolderId);
        }

        $existingSlugs = $query->pluck('public_slug')->all();

        if (! in_array($baseSlug, $existingSlugs, true)) {
            return $baseSlug;
        }

        $suffix = 2;

        while (in_array($baseSlug.'-'.$suffix, $existingSlugs, true)) {
            $suffix++;
        }

        return $baseSlug.'-'.$suffix;
    }

    public static function generateSlugFor(User $user, string $name, ?int $ignoreFolderId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'folder';
        $query = static::query()
            ->where('user_id', $user->id)
            ->where(function ($query) use ($baseSlug): void {
                $query->where('slug', $baseSlug)
                    ->orWhere('slug', 'like', $baseSlug.'-%');
            });

        if ($ignoreFolderId !== null) {
            $query->whereKeyNot($ignoreFolderId);
        }

        $existingSlugs = $query->pluck('slug')->all();

        if (! in_array($baseSlug, $existingSlugs, true)) {
            return $baseSlug;
        }

        $suffix = 2;

        while (in_array($baseSlug.'-'.$suffix, $existingSlugs, true)) {
            $suffix++;
        }

        return $baseSlug.'-'.$suffix;
    }
}
