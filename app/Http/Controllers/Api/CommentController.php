<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasBrandAccess($post->brand)) {
            return response()->json([
                'message' => 'You do not have access to this post.',
            ], 403);
        }

        $comments = $post->comments()
            ->with(['user', 'replies.user'])
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'comments' => $comments,
        ]);
    }

    public function store(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasBrandAccess($post->brand)) {
            return response()->json([
                'message' => 'You do not have access to this post.',
            ], 403);
        }

        $request->validate([
            'body' => ['required', 'string'],
            'parent_id' => ['nullable', 'exists:comments,id'],
            'attachment' => ['nullable', 'string'],
        ]);

        // If parent_id is provided, make sure it belongs to the same post
        if ($request->parent_id) {
            $parentComment = Comment::findOrFail($request->parent_id);
            if ($parentComment->post_id !== $post->id) {
                return response()->json([
                    'message' => 'Parent comment must belong to the same post.',
                ], 422);
            }
        }

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'parent_id' => $request->parent_id,
            'body' => $request->body,
            'attachment' => $request->attachment,
        ]);

        // TODO: Dispatch notification event for mentions

        return response()->json([
            'comment' => $comment->load('user'),
        ], 201);
    }

    public function update(Request $request, Comment $comment): JsonResponse
    {
        $user = $request->user();

        // Only the comment author can update
        if ($comment->user_id !== $user->id) {
            return response()->json([
                'message' => 'You can only edit your own comments.',
            ], 403);
        }

        $request->validate([
            'body' => ['required', 'string'],
        ]);

        $comment->update([
            'body' => $request->body,
        ]);

        return response()->json([
            'comment' => $comment->fresh('user'),
        ]);
    }

    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        $user = $request->user();

        // Only the comment author or managers can delete
        if ($comment->user_id !== $user->id && !$user->isManager()) {
            return response()->json([
                'message' => 'You can only delete your own comments.',
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully.',
        ]);
    }

    public function resolve(Request $request, Comment $comment): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasBrandAccess($comment->post->brand)) {
            return response()->json([
                'message' => 'You do not have access to this post.',
            ], 403);
        }

        $comment->update([
            'resolved' => !$comment->resolved,
        ]);

        return response()->json([
            'comment' => $comment->fresh('user'),
        ]);
    }
}
