<?php

namespace App\Http\Controllers;

use App\Http\Requests\Variant\StoreVariantRequest;
use App\Http\Requests\Variant\UpdateVariantRequest;
use App\Http\Resources\VariantResource;
use App\Models\Folder;
use App\Models\Variant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VariantController extends Controller
{
    public function index(Folder $folder): AnonymousResourceCollection
    {
        $this->authorize('view', $folder);

        return VariantResource::collection(
            $folder->variants()->orderBy('sort_order')->get()
        );
    }

    public function store(StoreVariantRequest $request, Folder $folder): JsonResponse
    {
        $this->authorize('update', $folder);

        $validated = $request->validated();
        $backfillPerformed = false;

        $defaultVariant = $folder->defaultVariant;

        if ($defaultVariant === null) {
            $defaultVariant = $folder->variants()->create([
                'name' => 'Default',
                'is_default' => true,
                'sort_order' => 0,
            ]);

            $folder->signs()->whereNull('variant_id')->update([
                'variant_id' => $defaultVariant->id,
            ]);

            $backfillPerformed = true;
        }

        $maxSortOrder = $folder->variants()->max('sort_order') ?? 0;

        $variant = $folder->variants()->create([
            'name' => $validated['name'],
            'is_default' => false,
            'sort_order' => $maxSortOrder + 1,
        ]);

        return response()->json([
            ...((new VariantResource($variant->refresh()))->toArray($request)),
            'backfill_performed' => $backfillPerformed,
        ], 201);
    }

    public function update(UpdateVariantRequest $request, Folder $folder, Variant $variant): JsonResponse
    {
        $this->authorize('update', $folder);

        if ($folder->id !== $variant->folder_id) {
            abort(404);
        }

        $validated = $request->validated();

        if (isset($validated['is_default']) && $validated['is_default']) {
            if (! $variant->is_default) {
                $oldDefault = $folder->defaultVariant;

                if ($oldDefault !== null && $oldDefault->id !== $variant->id) {
                    if ($oldDefault->name === 'Default') {
                        $oldDefault->update(['name' => 'Original', 'is_default' => false]);
                    } else {
                        $oldDefault->update(['is_default' => false]);
                    }
                }

                $variant->update(['is_default' => true, 'name' => null]);
            }
        } elseif (isset($validated['name'])) {
            $variant->update(['name' => $validated['name']]);
        }

        return (new VariantResource($variant->refresh()))->response();
    }

    public function destroy(Folder $folder, Variant $variant): JsonResponse
    {
        $this->authorize('update', $folder);

        if ($folder->id !== $variant->folder_id) {
            abort(404);
        }

        if ($variant->is_default) {
            return response()->json([
                'message' => 'Cannot delete the default variant. Set another variant as default first.',
            ], 409);
        }

        if ($folder->variants()->count() <= 1) {
            return response()->json([
                'message' => 'Cannot delete the last variant.',
            ], 409);
        }

        $variant->signs()->update(['variant_id' => null]);
        $variant->delete();

        return response()->json(['message' => 'Variant deleted.']);
    }
}
