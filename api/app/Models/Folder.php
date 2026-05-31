<?php

namespace App\Models;

use App\Enums\FolderVisibility;
use Database\Factories\FolderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'slug',
    'visibility',
    'password_hash',
])]
class Folder extends Model
{
    /** @use HasFactory<FolderFactory> */
    use HasFactory;

    protected $hidden = [
        'password_hash',
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
