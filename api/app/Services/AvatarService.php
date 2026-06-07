<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AvatarService
{
    public function upload(User $user, UploadedFile $file): string
    {
        $disk = config('filesystems.default');
        $key = 'avatars/'.$user->id.'/'.Str::uuid().'.'.$file->extension();

        if ($user->avatar_storage_key) {
            Storage::disk($disk)->delete($user->avatar_storage_key);
        }

        Storage::disk($disk)->put($key, $file->get(), 'public');

        $url = Storage::disk($disk)->url($key);

        $user->avatar_url = $url;
        $user->avatar_storage_key = $key;
        $user->save();

        return $url;
    }
}
