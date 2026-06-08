<?php

namespace App\Models;

use Database\Factories\SignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'folder_id',
    'variant_id',
    'name',
    'sort_key',
    'storage_disk',
    'storage_key',
    'public_url',
    'thumbnail_url',
    'mime_type',
    'size_bytes',
    'width',
    'height',
    'column_ratio',
])]
class Sign extends Model
{
    /** @use HasFactory<SignFactory> */
    use HasFactory;

    protected $hidden = [
        'storage_disk',
        'storage_key',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'column_ratio' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }
}
