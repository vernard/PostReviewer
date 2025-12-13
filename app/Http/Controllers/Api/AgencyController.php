<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgencyController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;

        return response()->json([
            'agency' => $agency,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'Only admins can update agency settings.',
            ], 403);
        }

        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'logo' => ['sometimes', 'nullable', 'string'],
            'settings' => ['sometimes', 'array'],
        ]);

        $agency = $user->agency;
        $agency->update($request->only(['name', 'logo', 'settings']));

        return response()->json([
            'agency' => $agency->fresh(),
        ]);
    }

    public function storage(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;

        return response()->json([
            'storage_used' => $agency->storage_used,
            'storage_quota' => $agency->storage_quota,
            'storage_used_formatted' => $agency->storage_used_formatted,
            'storage_quota_formatted' => $agency->storage_quota_formatted,
            'storage_percentage' => $agency->storage_percentage,
            'is_near_limit' => $agency->storage_percentage >= 80,
            'is_over_limit' => $agency->storage_percentage >= 100,
        ]);
    }

    public function dashboardStats(Request $request): JsonResponse
    {
        $user = $request->user();
        $agencyId = $user->agency_id;

        // Get brand IDs the user has access to
        if ($user->isManager()) {
            $brandIds = $user->agency->brands()->pluck('id');
        } else {
            $brandIds = $user->brands()->pluck('brands.id');
        }

        $stats = [
            'total_posts' => Post::whereIn('brand_id', $brandIds)->count(),
            'pending_approval' => Post::whereIn('brand_id', $brandIds)
                ->where('status', 'pending_approval')
                ->count(),
            'approved' => Post::whereIn('brand_id', $brandIds)
                ->where('status', 'approved')
                ->count(),
            'brands_count' => $brandIds->count(),
        ];

        // Recent activity - last 10 posts
        $recentPosts = Post::with(['brand', 'creator'])
            ->whereIn('brand_id', $brandIds)
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get();

        return response()->json([
            'stats' => $stats,
            'recent_posts' => $recentPosts,
        ]);
    }
}
