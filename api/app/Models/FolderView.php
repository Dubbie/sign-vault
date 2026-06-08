<?php

namespace App\Models;

use App\Enums\FolderViewType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolderView extends Model
{
    protected $fillable = [
        'folder_id',
        'ip_hash',
        'view_type',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'view_type' => FolderViewType::class,
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }
}
