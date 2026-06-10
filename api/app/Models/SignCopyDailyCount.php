<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignCopyDailyCount extends Model
{
    protected $fillable = [
        'sign_id',
        'folder_id',
        'date',
        'count',
    ];

    public function sign(): BelongsTo
    {
        return $this->belongsTo(Sign::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }
}
