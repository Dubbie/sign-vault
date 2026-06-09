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

    /**
     * @param  list<string>  $ids
     * @return array<string, PreparedSignUpload>
     */
    public function findMany(array $ids): array
    {
        $keyMap = array_combine(
            array_map(fn (string $id) => $this->cacheKey($id), $ids),
            $ids,
        );

        $payloads = Cache::getMultiple(array_keys($keyMap));

        $uploads = [];

        foreach ($payloads as $cacheKey => $payload) {
            if (is_array($payload)) {
                $uploads[$keyMap[$cacheKey]] = PreparedSignUpload::fromArray($payload);
            }
        }

        return $uploads;
    }

    public function forget(string $id): void
    {
        Cache::forget($this->cacheKey($id));
    }

    /**
     * @param  list<string>  $ids
     */
    public function forgetMany(array $ids): void
    {
        Cache::deleteMultiple(array_map(fn (string $id) => $this->cacheKey($id), $ids));
    }

    private function cacheKey(string $id): string
    {
        return "prepared-sign-upload:{$id}";
    }
}
