<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\ApprovalRequest;
use App\Models\Brand;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'users' => [
                'total' => User::count(),
                'by_role' => User::select('role', DB::raw('count(*) as count'))
                    ->groupBy('role')
                    ->pluck('count', 'role'),
            ],
            'agencies' => [
                'total' => Agency::count(),
            ],
            'brands' => [
                'total' => Brand::count(),
            ],
            'posts' => [
                'total' => Post::count(),
                'by_status' => Post::select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status'),
            ],
            'approvals' => [
                'total' => ApprovalRequest::count(),
                'by_status' => ApprovalRequest::select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status'),
                'approval_rate' => $this->calculateApprovalRate(),
            ],
            'recent_activity' => $this->getRecentActivity(),
        ];

        return response()->json($stats);
    }

    public function users(Request $request)
    {
        $query = User::with(['agency', 'brands'])
            ->withCount(['posts', 'brands']);

        // Sort by power users (most posts/brands)
        $sortBy = $request->get('sort', 'posts_count');
        $sortDir = $request->get('direction', 'desc');

        if (in_array($sortBy, ['posts_count', 'brands_count', 'created_at', 'name', 'email'])) {
            $query->orderBy($sortBy, $sortDir);
        }

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(20);

        return response()->json($users);
    }

    public function impersonate(Request $request, User $user)
    {
        $admin = $request->user();

        // Log the impersonation
        Log::info('Super admin impersonation started', [
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
            'target_user_id' => $user->id,
            'target_user_email' => $user->email,
            'ip' => $request->ip(),
        ]);

        // Create a token for the target user
        $token = $user->createToken('impersonation', ['impersonated'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->load(['agency', 'brands']),
            'message' => "Now logged in as {$user->name}",
        ]);
    }

    public function agencies(Request $request)
    {
        // Sort options
        $sortBy = $request->get('sort', 'created_at');
        $sortDir = $request->get('direction', 'desc');
        $search = $request->get('search');

        // Build query with subqueries for counts (avoids GROUP BY issues)
        $query = Agency::select('agencies.*')
            ->withCount(['users', 'brands'])
            ->addSelect([
                'posts_count' => Post::selectRaw('COUNT(*)')
                    ->join('brands', 'posts.brand_id', '=', 'brands.id')
                    ->whereColumn('brands.agency_id', 'agencies.id'),
                'storage_bytes' => Media::selectRaw('COALESCE(SUM(size), 0)')
                    ->join('brands', 'media.brand_id', '=', 'brands.id')
                    ->whereColumn('brands.agency_id', 'agencies.id'),
                'media_count' => Media::selectRaw('COUNT(*)')
                    ->join('brands', 'media.brand_id', '=', 'brands.id')
                    ->whereColumn('brands.agency_id', 'agencies.id'),
            ]);

        // Search
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Sort
        if (in_array($sortBy, ['users_count', 'brands_count', 'posts_count', 'storage_bytes', 'media_count', 'created_at', 'name'])) {
            $query->orderBy($sortBy, $sortDir);
        }

        $agencies = $query->paginate(20);

        // Enrich with additional stats
        $agencyIds = $agencies->pluck('id');

        // Get posts stats per agency
        $postsStats = Post::join('brands', 'posts.brand_id', '=', 'brands.id')
            ->whereIn('brands.agency_id', $agencyIds)
            ->select(
                'brands.agency_id',
                DB::raw('COUNT(*) as total_posts'),
                DB::raw('SUM(CASE WHEN posts.status = "approved" THEN 1 ELSE 0 END) as approved_posts'),
                DB::raw('SUM(CASE WHEN posts.status = "pending_approval" THEN 1 ELSE 0 END) as pending_posts'),
                DB::raw('SUM(CASE WHEN posts.status = "draft" THEN 1 ELSE 0 END) as draft_posts')
            )
            ->groupBy('brands.agency_id')
            ->get()
            ->keyBy('agency_id');

        // Add stats to each agency
        $agencies->getCollection()->transform(function ($agency) use ($postsStats) {
            $stats = $postsStats->get($agency->id);
            $agency->posts_count = $stats?->total_posts ?? 0;
            $agency->approved_posts = $stats?->approved_posts ?? 0;
            $agency->pending_posts = $stats?->pending_posts ?? 0;
            $agency->draft_posts = $stats?->draft_posts ?? 0;
            return $agency;
        });

        return response()->json($agencies);
    }

    private function calculateApprovalRate(): float
    {
        $total = ApprovalRequest::count();
        if ($total === 0) {
            return 0;
        }

        $approved = ApprovalRequest::where('status', 'approved')->count();

        return round(($approved / $total) * 100, 1);
    }

    private function getRecentActivity(): array
    {
        $recentPosts = Post::with(['creator:id,name', 'brand:id,name'])
            ->select('id', 'title', 'status', 'created_by', 'brand_id', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($post) => [
                'type' => 'post',
                'id' => $post->id,
                'title' => $post->title,
                'status' => $post->status,
                'user' => $post->creator?->name ?? 'Unknown',
                'brand' => $post->brand?->name ?? 'Unknown',
                'created_at' => $post->created_at,
            ]);

        $recentUsers = User::with('agency:id,name')
            ->select('id', 'name', 'email', 'agency_id', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($user) => [
                'type' => 'user',
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'agency' => $user->agency?->name ?? 'Unknown',
                'created_at' => $user->created_at,
            ]);

        return collect($recentPosts)
            ->merge($recentUsers)
            ->sortByDesc('created_at')
            ->take(15)
            ->values()
            ->toArray();
    }
}
