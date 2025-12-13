<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicApprovalController extends Controller
{
    /**
     * Get collection for public approval review.
     */
    public function show(string $token)
    {
        $collection = Collection::where('approval_token', $token)
            ->where(function ($query) {
                $query->whereNull('approval_token_expires_at')
                    ->orWhere('approval_token_expires_at', '>', now());
            })
            ->with(['brand:id,name,logo_path', 'posts.media'])
            ->first();

        if (!$collection) {
            return response()->json([
                'message' => 'Invalid or expired approval link.',
            ], 404);
        }

        // Transform brand logo path to URL
        if ($collection->brand && $collection->brand->logo_path) {
            $collection->brand->logo_url = asset('storage/' . $collection->brand->logo_path);
        }

        // Transform media paths to URLs
        foreach ($collection->posts as $post) {
            foreach ($post->media as $media) {
                $media->url = asset('storage/' . $media->file_path);
            }
        }

        return response()->json($collection);
    }

    /**
     * Submit approval reviews for posts in a collection.
     */
    public function submit(Request $request, string $token)
    {
        $collection = Collection::where('approval_token', $token)
            ->where(function ($query) {
                $query->whereNull('approval_token_expires_at')
                    ->orWhere('approval_token_expires_at', '>', now());
            })
            ->first();

        if (!$collection) {
            return response()->json([
                'message' => 'Invalid or expired approval link.',
            ], 404);
        }

        $validated = $request->validate([
            'reviews' => 'required|array',
            'reviews.*.post_id' => 'required|integer|exists:posts,id',
            'reviews.*.status' => 'required|in:approved,changes_requested',
            'reviews.*.feedback' => 'nullable|string|max:2000',
            'reviews.*.caption_suggestion' => 'nullable|string|max:2000',
        ]);

        // Verify all posts belong to this collection
        $collectionPostIds = $collection->posts()->pluck('id')->toArray();
        foreach ($validated['reviews'] as $review) {
            if (!in_array($review['post_id'], $collectionPostIds)) {
                return response()->json([
                    'message' => 'One or more posts do not belong to this collection.',
                ], 422);
            }
        }

        DB::transaction(function () use ($validated, $collection) {
            foreach ($validated['reviews'] as $review) {
                $post = Post::find($review['post_id']);

                $updateData = [
                    'status' => $review['status'],
                ];

                // Store feedback in metadata
                $metadata = $post->metadata ?? [];

                if (!empty($review['feedback'])) {
                    $metadata['client_feedback'] = $review['feedback'];
                    $metadata['feedback_at'] = now()->toIso8601String();
                }

                if (!empty($review['caption_suggestion'])) {
                    $metadata['caption_suggestion'] = $review['caption_suggestion'];
                }

                $updateData['metadata'] = $metadata;
                $post->update($updateData);
            }

            // Update collection metadata to mark as reviewed
            $collection->update([
                'metadata' => array_merge($collection->metadata ?? [], [
                    'last_reviewed_at' => now()->toIso8601String(),
                ]),
            ]);
        });

        return response()->json([
            'message' => 'Review submitted successfully.',
        ]);
    }
}
