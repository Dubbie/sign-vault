<?php

namespace App\Services;

use App\Models\PreparedSignUpload;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PreparedSignUploadService
{
    private const TTL_MINUTES = 15;

    public function store(PreparedSignUpload $upload): void
    {
        Cache::put($this->cacheKey($upload->id), $upload->toArray(), now()->addMinutes(self::TTL_MINUTES));
    }

    public function makeId(): string
    {
        return (string) Str::uuid();
    }

    public function find(string $id): ?PreparedSignUpload
    {
        $payload = Cache::get($this->cacheKey($id));

        if (! is_array($payload)) {
            return null;
        }

        return PreparedSignUpload::fromArray($payload);
    }

    public function forget(string $id): void
    {
        Cache::forget($this->cacheKey($id));
    }

    private function cacheKey(string $id): string
    {
        return "prepared-sign-upload:{$id}";
    }
}
