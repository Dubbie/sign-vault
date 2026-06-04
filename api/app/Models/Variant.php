<?php

namespace App\Models;

use Database\Factories\VariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'folder_id',
    'name',
    'is_default',
    'sort_order',
])]
class Variant extends Model
{
    /** @use HasFactory<VariantFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function signs(): HasMany
    {
        return $this->hasMany(Sign::class);
    }
}
