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
    'name',
    'storage_disk',
    'storage_key',
    'public_url',
    'mime_type',
    'size_bytes',
    'width',
    'height',
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
}
