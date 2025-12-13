<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Collection;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get brand IDs the user has access to
        if ($user->isManager()) {
            $brandIds = $user->agency->brands()->pluck('id');
        } else {
            $brandIds = $user->brands()->pluck('brands.id');
        }

        $query = Collection::with(['brand', 'creator'])
            ->withCount('posts')
            ->whereIn('brand_id', $brandIds);

        // Filter by brand
        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        $collections = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        // Add status summary to each collection
        $collections->getCollection()->transform(function ($collection) {
            $collection->status_summary = $collection->getStatusSummary();
            $collection->approval_url = $collection->getApprovalUrl();
            return $collection;
        });

        return response()->json($collections);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'brand_id' => ['required', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'posts' => ['required', 'array', 'min:1'],
            'posts.*.title' => ['required', 'string', 'max:255'],
            'posts.*.caption' => ['nullable', 'string'],
            'posts.*.platforms' => ['required', 'array', 'min:1'],
            'posts.*.platforms.*' => ['string', 'in:facebook_feed,facebook_story,instagram_feed,instagram_story,instagram_reel'],
            'posts.*.media_id' => ['required', 'exists:media,id'],
        ]);

        $brand = Brand::findOrFail($request->brand_id);

        if (!$user->hasBrandAccess($brand)) {
            return response()->json([
                'message' => 'You do not have access to this brand.',
            ], 403);
        }

        // Create collection
        $collection = Collection::create([
            'brand_id' => $brand->id,
            'created_by' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        // Create posts within the collection
        $createdPosts = [];
        foreach ($request->posts as $postData) {
            $post = Post::create([
                'brand_id' => $brand->id,
                'collection_id' => $collection->id,
                'created_by' => $user->id,
                'title' => $postData['title'],
                'caption' => $postData['caption'] ?? null,
                'platforms' => $postData['platforms'],
                'status' => 'draft',
            ]);

            // Attach media
            $post->media()->attach($postData['media_id'], ['position' => 0]);

            $createdPosts[] = $post->load('media');
        }

        return response()->json([
            'collection' => $collection->load(['brand', 'creator']),
            'posts' => $createdPosts,
        ], 201);
    }

    public function show(Request $request, Collection $collection): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasBrandAccess($collection->brand)) {
            return response()->json([
                'message' => 'You do not have access to this collection.',
            ], 403);
        }

        $collection->load(['brand', 'creator', 'posts.media', 'posts.creator']);
        $collection->status_summary = $collection->getStatusSummary();
        $collection->approval_url = $collection->getApprovalUrl();

        return response()->json(['collection' => $collection]);
    }

    public function update(Request $request, Collection $collection): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasBrandAccess($collection->brand)) {
            return response()->json([
                'message' => 'You do not have access to this collection.',
            ], 403);
        }

        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $collection->update($request->only(['name', 'description']));

        return response()->json(['collection' => $collection]);
    }

    public function destroy(Request $request, Collection $collection): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasBrandAccess($collection->brand)) {
            return response()->json([
                'message' => 'You do not have access to this collection.',
            ], 403);
        }

        // Note: Posts will have collection_id set to null due to onDelete('set null')
        $collection->delete();

        return response()->json(['message' => 'Collection deleted successfully.']);
    }

    /**
     * Generate or regenerate approval token for public review
     */
    public function generateApprovalLink(Request $request, Collection $collection): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasBrandAccess($collection->brand)) {
            return response()->json([
                'message' => 'You do not have access to this collection.',
            ], 403);
        }

        $request->validate([
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $expiresInDays = $request->get('expires_in_days', 30);
        $collection->generateApprovalToken($expiresInDays);

        return response()->json([
            'approval_url' => $collection->getApprovalUrl(),
            'expires_at' => $collection->approval_token_expires_at,
        ]);
    }

    /**
     * Submit all posts in collection for approval
     */
    public function submitForApproval(Request $request, Collection $collection): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasBrandAccess($collection->brand)) {
            return response()->json([
                'message' => 'You do not have access to this collection.',
            ], 403);
        }

        $posts = $collection->posts()->whereIn('status', ['draft', 'changes_requested'])->get();

        if ($posts->isEmpty()) {
            return response()->json([
                'message' => 'No posts available to submit for approval.',
            ], 400);
        }

        foreach ($posts as $post) {
            $post->update(['status' => 'pending_approval']);

            // Create approval request if needed
            \App\Models\ApprovalRequest::create([
                'post_id' => $post->id,
                'requested_by' => $user->id,
                'status' => 'pending',
            ]);
        }

        // Generate approval link if not exists
        if (!$collection->hasValidApprovalToken()) {
            $collection->generateApprovalToken();
        }

        return response()->json([
            'message' => 'Posts submitted for approval.',
            'approval_url' => $collection->getApprovalUrl(),
            'posts_submitted' => $posts->count(),
        ]);
    }

    /**
     * Add existing posts to a collection
     */
    public function addPosts(Request $request, Collection $collection): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasBrandAccess($collection->brand)) {
            return response()->json([
                'message' => 'You do not have access to this collection.',
            ], 403);
        }

        $request->validate([
            'post_ids' => ['required', 'array', 'min:1'],
            'post_ids.*' => ['exists:posts,id'],
        ]);

        $posts = Post::whereIn('id', $request->post_ids)
            ->where('brand_id', $collection->brand_id)
            ->get();

        foreach ($posts as $post) {
            $post->update(['collection_id' => $collection->id]);
        }

        return response()->json([
            'message' => 'Posts added to collection.',
            'posts_added' => $posts->count(),
        ]);
    }

    /**
     * Remove posts from a collection
     */
    public function removePosts(Request $request, Collection $collection): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasBrandAccess($collection->brand)) {
            return response()->json([
                'message' => 'You do not have access to this collection.',
            ], 403);
        }

        $request->validate([
            'post_ids' => ['required', 'array', 'min:1'],
            'post_ids.*' => ['exists:posts,id'],
        ]);

        Post::whereIn('id', $request->post_ids)
            ->where('collection_id', $collection->id)
            ->update(['collection_id' => null]);

        return response()->json([
            'message' => 'Posts removed from collection.',
        ]);
    }
}
