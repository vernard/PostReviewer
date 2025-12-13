<?php

namespace App\Http\Controllers\Api;

use App\Events\BrandChanged;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BrandController extends Controller
{
    /**
     * Flatten a PNG image to JPG with white background.
     * Returns the path to the flattened image, or null if not a PNG.
     */
    private function flattenLogoToJpg(string $originalPath): ?string
    {
        $fullPath = Storage::disk('public')->path($originalPath);
        $mimeType = mime_content_type($fullPath);

        // Only process PNG files
        if ($mimeType !== 'image/png') {
            return null;
        }

        $png = imagecreatefrompng($fullPath);
        if (!$png) {
            return null;
        }

        $width = imagesx($png);
        $height = imagesy($png);

        // Create a new image with white background
        $jpg = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($jpg, 255, 255, 255);
        imagefill($jpg, 0, 0, $white);

        // Preserve transparency by compositing PNG onto white background
        imagealphablending($jpg, true);
        imagecopy($jpg, $png, 0, 0, 0, 0, $width, $height);

        // Generate flattened file path
        $flatPath = preg_replace('/\.png$/i', '_flat.jpg', $originalPath);
        $flatFullPath = Storage::disk('public')->path($flatPath);

        // Save as JPG with high quality
        imagejpeg($jpg, $flatFullPath, 95);

        // Clean up
        imagedestroy($png);
        imagedestroy($jpg);

        // Set proper ownership
        chown($flatFullPath, 33);
        chgrp($flatFullPath, 33);

        return $flatPath;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isManager()) {
            $brands = $user->agency->brands()->withCount(['posts', 'users'])->get();
        } else {
            $brands = $user->brands()->withCount(['posts', 'users'])->get();
        }

        return response()->json([
            'brands' => $brands,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isManager()) {
            return response()->json([
                'message' => 'Only managers can create brands.',
            ], 403);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'color_scheme' => ['nullable', 'array'],
            'profile_name' => ['nullable', 'string', 'max:255'],
            'profile_avatar' => ['nullable', 'string'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $data = $request->only(['name', 'description', 'color_scheme', 'profile_name', 'profile_avatar']);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brands', 'public');
            $data['logo_flat'] = $this->flattenLogoToJpg($data['logo']);
        }

        $brand = Brand::create([
            'agency_id' => $user->agency_id,
            ...$data,
        ]);

        // Attach users (always include creator)
        $userIds = $request->user_ids ?? [];
        $userIds[] = $user->id;
        // Filter to only include users from the same agency
        $validUserIds = User::where('agency_id', $user->agency_id)
            ->whereIn('id', array_unique($userIds))
            ->pluck('id')
            ->toArray();
        $brand->users()->attach($validUserIds);

        // Broadcast brand created event
        broadcast(new BrandChanged('created', $user->agency_id, $brand->id, $brand))->toOthers();

        return response()->json([
            'brand' => $brand->load('users'),
        ], 201);
    }

    public function show(Request $request, Brand $brand): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasBrandAccess($brand)) {
            return response()->json([
                'message' => 'You do not have access to this brand.',
            ], 403);
        }

        $brand->load('users');
        $brand->loadCount(['posts', 'media']);

        return response()->json([
            'brand' => $brand,
        ]);
    }

    public function update(Request $request, Brand $brand): JsonResponse
    {
        $user = $request->user();

        if (!$user->isManager() || $user->agency_id !== $brand->agency_id) {
            return response()->json([
                'message' => 'Only managers can update brands.',
            ], 403);
        }

        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'color_scheme' => ['nullable', 'array'],
            'profile_name' => ['nullable', 'string', 'max:255'],
            'profile_avatar' => ['nullable', 'string'],
            'settings' => ['nullable', 'array'],
        ]);

        $data = $request->only([
            'name', 'description', 'color_scheme', 'profile_name', 'profile_avatar', 'settings'
        ]);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brands', 'public');
            $data['logo_flat'] = $this->flattenLogoToJpg($data['logo']);
        }

        $brand->update($data);

        $freshBrand = $brand->fresh();

        // Broadcast brand updated event
        broadcast(new BrandChanged('updated', $user->agency_id, $brand->id, $freshBrand))->toOthers();

        return response()->json([
            'brand' => $freshBrand,
        ]);
    }

    public function destroy(Request $request, Brand $brand): JsonResponse
    {
        $user = $request->user();

        if (!$user->isAdmin() || $user->agency_id !== $brand->agency_id) {
            return response()->json([
                'message' => 'Only admins can delete brands.',
            ], 403);
        }

        $agencyId = $brand->agency_id;
        $brandId = $brand->id;

        $brand->delete();

        // Broadcast brand deleted event
        broadcast(new BrandChanged('deleted', $agencyId, $brandId))->toOthers();

        return response()->json([
            'message' => 'Brand deleted successfully.',
        ]);
    }

    public function addUser(Request $request, Brand $brand): JsonResponse
    {
        $authUser = $request->user();

        if (!$authUser->isManager() || $authUser->agency_id !== $brand->agency_id) {
            return response()->json([
                'message' => 'Only managers can assign users to brands.',
            ], 403);
        }

        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $user = User::findOrFail($request->user_id);

        if ($user->agency_id !== $brand->agency_id) {
            return response()->json([
                'message' => 'User must be in the same agency.',
            ], 422);
        }

        $brand->users()->syncWithoutDetaching([$user->id]);

        return response()->json([
            'message' => 'User added to brand.',
            'brand' => $brand->load('users'),
        ]);
    }

    public function removeUser(Request $request, Brand $brand, User $user): JsonResponse
    {
        $authUser = $request->user();

        if (!$authUser->isManager() || $authUser->agency_id !== $brand->agency_id) {
            return response()->json([
                'message' => 'Only managers can remove users from brands.',
            ], 403);
        }

        $brand->users()->detach($user->id);

        return response()->json([
            'message' => 'User removed from brand.',
            'brand' => $brand->load('users'),
        ]);
    }

    public function getDefaultReviewers(Request $request, Brand $brand): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasBrandAccess($brand)) {
            return response()->json([
                'message' => 'You do not have access to this brand.',
            ], 403);
        }

        return response()->json([
            'default_reviewers' => $brand->default_reviewers ?? [],
        ]);
    }
}
