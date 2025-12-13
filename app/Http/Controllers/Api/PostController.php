<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PostSubmittedForApprovalMail;
use App\Models\ApprovalRequest;
use App\Models\Brand;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PostController extends Controller
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

        $query = Post::with(['brand', 'creator', 'media'])
            ->whereIn('brand_id', $brandIds);

        // Filter by brand
        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by platform
        if ($request->has('platform')) {
            $query->whereJsonContains('platforms', $request->platform);
        }

        // Filter by creator (my posts)
        if ($request->boolean('mine')) {
            $query->where('created_by', $user->id);
        }

        $posts = $query->orderBy('updated_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json($posts);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'brand_id' => ['required', 'exists:brands,id'],
            'title' => ['required', 'string', 'max:255'],
            'caption' => ['nullable', 'string'],
            'platforms' => ['required', 'array', 'min:1'],
            'platforms.*' => ['string', 'in:facebook_feed,facebook_story,instagram_feed,instagram_story,instagram_reel'],
            'media_ids' => ['nullable', 'array'],
            'media_ids.*' => ['exists:media,id'],
        ]);

        $brand = Brand::findOrFail($request->brand_id);

        if (!$user->hasBrandAccess($brand)) {
            return response()->json([
                'message' => 'You do not have access to this brand.',
            ], 403);
        }

        $post = Post::create([
            'brand_id' => $brand->id,
            'created_by' => $user->id,
            'title' => $request->title,
            'caption' => $request->caption,
            'platforms' => $request->platforms,
            'status' => 'draft',
        ]);

        // Attach media if provided
        if ($request->has('media_ids')) {
            foreach ($request->media_ids as $position => $mediaId) {
                $post->media()->attach($mediaId, ['position' => $position]);
            }
        }

        return response()->json([
            'post' => $post->load(['brand', 'creator', 'media']),
        ], 201);
    }

    public function show(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasBrandAccess($post->brand)) {
            return response()->json([
                'message' => 'You do not have access to this post.',
            ], 403);
        }

        $post->load(['brand', 'creator', 'media', 'comments.user', 'latestApprovalRequest.responses.user']);

        return response()->json([
            'post' => $post,
        ]);
    }

    public function update(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasBrandAccess($post->brand)) {
            return response()->json([
                'message' => 'You do not have access to this post.',
            ], 403);
        }

        if (!$post->canBeEdited()) {
            return response()->json([
                'message' => 'This post cannot be edited in its current status.',
            ], 422);
        }

        // Only creator or managers can edit
        if ($post->created_by !== $user->id && !$user->isManager()) {
            return response()->json([
                'message' => 'You can only edit your own posts.',
            ], 403);
        }

        $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'caption' => ['nullable', 'string'],
            'platforms' => ['sometimes', 'array', 'min:1'],
            'platforms.*' => ['string', 'in:facebook_feed,facebook_story,instagram_feed,instagram_story,instagram_reel'],
        ]);

        $post->update($request->only(['title', 'caption', 'platforms']));

        return response()->json([
            'post' => $post->fresh(['brand', 'creator', 'media']),
        ]);
    }

    public function destroy(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasBrandAccess($post->brand)) {
            return response()->json([
                'message' => 'You do not have access to this post.',
            ], 403);
        }

        // Only creator or managers can delete
        if ($post->created_by !== $user->id && !$user->isManager()) {
            return response()->json([
                'message' => 'You can only delete your own posts.',
            ], 403);
        }

        $post->delete();

        return response()->json([
            'message' => 'Post deleted successfully.',
        ]);
    }

    public function submitForApproval(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasBrandAccess($post->brand)) {
            return response()->json([
                'message' => 'You do not have access to this post.',
            ], 403);
        }

        if (!$post->canBeSubmittedForApproval()) {
            return response()->json([
                'message' => 'This post cannot be submitted for approval.',
            ], 422);
        }

        // Create approval request
        ApprovalRequest::create([
            'post_id' => $post->id,
            'requested_by' => $user->id,
            'status' => 'pending',
            'due_date' => $request->due_date,
        ]);

        $post->update(['status' => 'pending_approval']);

        // Notify reviewers (managers in the same agency)
        $reviewers = User::where('agency_id', $user->agency_id)
            ->whereIn('role', ['admin', 'manager', 'reviewer'])
            ->where('id', '!=', $user->id)
            ->get();

        foreach ($reviewers as $reviewer) {
            Mail::to($reviewer->email)->queue(new PostSubmittedForApprovalMail($post));
        }

        return response()->json([
            'post' => $post->fresh(['brand', 'creator', 'media', 'latestApprovalRequest']),
            'message' => 'Post submitted for approval.',
        ]);
    }

    public function duplicate(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasBrandAccess($post->brand)) {
            return response()->json([
                'message' => 'You do not have access to this post.',
            ], 403);
        }

        $newPost = $post->replicate(['status', 'scheduled_for']);
        $newPost->title = $post->title . ' (Copy)';
        $newPost->status = 'draft';
        $newPost->created_by = $user->id;
        $newPost->save();

        // Copy media attachments
        foreach ($post->media as $media) {
            $newPost->media()->attach($media->id, [
                'position' => $media->pivot->position,
                'platform_overrides' => $media->pivot->platform_overrides,
            ]);
        }

        return response()->json([
            'post' => $newPost->load(['brand', 'creator', 'media']),
        ], 201);
    }

    public function attachMedia(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasBrandAccess($post->brand)) {
            return response()->json([
                'message' => 'You do not have access to this post.',
            ], 403);
        }

        if (!$post->canBeEdited()) {
            return response()->json([
                'message' => 'This post cannot be edited.',
            ], 422);
        }

        $request->validate([
            'media_id' => ['required', 'exists:media,id'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        $media = Media::findOrFail($request->media_id);

        if ($media->brand_id !== $post->brand_id) {
            return response()->json([
                'message' => 'Media must belong to the same brand.',
            ], 422);
        }

        $position = $request->position ?? $post->media()->count();
        $post->media()->attach($media->id, ['position' => $position]);

        return response()->json([
            'post' => $post->fresh('media'),
        ]);
    }

    public function detachMedia(Request $request, Post $post, Media $media): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasBrandAccess($post->brand)) {
            return response()->json([
                'message' => 'You do not have access to this post.',
            ], 403);
        }

        if (!$post->canBeEdited()) {
            return response()->json([
                'message' => 'This post cannot be edited.',
            ], 422);
        }

        $post->media()->detach($media->id);

        return response()->json([
            'post' => $post->fresh('media'),
        ]);
    }

    public function reorderMedia(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasBrandAccess($post->brand)) {
            return response()->json([
                'message' => 'You do not have access to this post.',
            ], 403);
        }

        if (!$post->canBeEdited()) {
            return response()->json([
                'message' => 'This post cannot be edited.',
            ], 422);
        }

        $request->validate([
            'media_ids' => ['required', 'array'],
            'media_ids.*' => ['integer'],
        ]);

        foreach ($request->media_ids as $position => $mediaId) {
            $post->media()->updateExistingPivot($mediaId, ['position' => $position]);
        }

        return response()->json([
            'post' => $post->fresh('media'),
        ]);
    }
}
