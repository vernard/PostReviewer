<?php

namespace App\Http\Controllers\Api;

use App\Events\BrandChanged;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
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
}
