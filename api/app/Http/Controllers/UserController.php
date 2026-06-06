<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UploadAvatarRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function linkedProviders(Request $request): JsonResponse
    {
        $providers = $request->user()
            ->oauthProviders()
            ->get(['provider', 'username', 'display_name', 'avatar_url'])
            ->map(fn ($p) => [
                'provider'     => $p->provider,
                'username'     => $p->username,
                'display_name' => $p->display_name,
                'avatar_url'   => $p->avatar_url,
            ]);

        return response()->json(['providers' => $providers]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->display_name = $request->validated()['display_name'];
        $user->save();

        return response()->json(['display_name' => $user->display_name]);
    }

    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $user = $request->user();
        $disk = config('filesystems.default');
        $file = $request->file('avatar');

        $ext = $file->extension();
        $key = 'avatars/'.$user->id.'/'.Str::uuid().'.'.$ext;

        // Delete the previous custom avatar if one exists.
        if ($user->avatar_storage_key) {
            Storage::disk($disk)->delete($user->avatar_storage_key);
        }

        Storage::disk($disk)->put($key, $file->get(), 'public');

        $user->avatar_url = Storage::disk($disk)->url($key);
        $user->avatar_storage_key = $key;
        $user->save();

        return response()->json(['avatar_url' => $user->avatar_url]);
    }
}
