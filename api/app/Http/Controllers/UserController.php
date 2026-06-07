<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UploadAvatarRequest;
use App\Services\AvatarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private AvatarService $avatarService) {}

    public function linkedProviders(Request $request): JsonResponse
    {
        $providers = $request->user()
            ->oauthProviders()
            ->get(['provider', 'username', 'display_name', 'avatar_url'])
            ->map(fn ($p) => [
                'provider' => $p->provider,
                'username' => $p->username,
                'display_name' => $p->display_name,
                'avatar_url' => $p->avatar_url,
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
        $url = $this->avatarService->upload($request->user(), $request->file('avatar'));

        return response()->json(['avatar_url' => $url]);
    }
}
