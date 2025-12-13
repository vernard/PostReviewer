<?php

namespace App\Http\Controllers;

use App\Mail\PostApprovedMail;
use App\Mail\PostChangesRequestedMail;
use App\Models\ApprovalInvite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PublicReviewController extends Controller
{
    /**
     * Find and validate an invite by token.
     */
    private function findValidInvite(string $token): ?ApprovalInvite
    {
        $invite = ApprovalInvite::where('token', $token)
            ->with(['approvalRequest.post.brand', 'approvalRequest.post.media', 'approvalRequest.requester'])
            ->first();

        if (!$invite) {
            return null;
        }

        if ($invite->isExpired()) {
            return null;
        }

        if (!$invite->approvalRequest->isPending()) {
            return null;
        }

        return $invite;
    }

    /**
     * Get post for public review.
     */
    public function show(string $token): JsonResponse
    {
        $invite = $this->findValidInvite($token);

        if (!$invite) {
            return response()->json([
                'message' => 'Invalid or expired review link.',
            ], 404);
        }

        $post = $invite->approvalRequest->post;
        $brand = $post->brand;

        // Transform media paths to URLs
        foreach ($post->media as $media) {
            $media->url = asset('storage/' . $media->file_path);
            if ($media->thumbnail_path) {
                $media->thumbnail_url = asset('storage/' . $media->thumbnail_path);
            }
        }

        return response()->json([
            'invite' => [
                'email' => $invite->email,
                'expires_at' => $invite->expires_at,
                'has_responded' => $invite->hasResponded(),
            ],
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'caption' => $post->caption,
                'platforms' => $post->platforms,
                'status' => $post->status,
                'media' => $post->media,
            ],
            'brand' => [
                'id' => $brand->id,
                'name' => $brand->name,
                'logo_url' => $brand->logo_url,
                'logo_flat_url' => $brand->logo_flat_url,
            ],
            'requester' => [
                'name' => $invite->approvalRequest->requester->name,
            ],
        ]);
    }

    /**
     * Approve the post via invite token.
     */
    public function approve(Request $request, string $token): JsonResponse
    {
        $invite = $this->findValidInvite($token);

        if (!$invite) {
            return response()->json([
                'message' => 'Invalid or expired review link.',
            ], 404);
        }

        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $approvalRequest = $invite->approvalRequest;
        $post = $approvalRequest->post;

        DB::transaction(function () use ($invite, $approvalRequest, $post, $validated) {
            // Store who approved in metadata (external reviewers don't have user accounts)
            $metadata = $post->metadata ?? [];
            $metadata['approved_by_email'] = $invite->email;
            $metadata['approved_at'] = now()->toIso8601String();
            if (!empty($validated['comment'])) {
                $metadata['approval_comment'] = $validated['comment'];
            }

            // Update approval request and post status
            $approvalRequest->update(['status' => 'approved']);
            $post->update([
                'status' => 'approved',
                'metadata' => $metadata,
            ]);

            // Mark invite as responded
            $invite->markResponded();
        });

        // Notify the post creator
        Mail::to($post->creator->email)->queue(
            new PostApprovedMail($post, $invite->email)
        );

        return response()->json([
            'message' => 'Post approved successfully.',
            'post_status' => 'approved',
        ]);
    }

    /**
     * Request changes via invite token.
     */
    public function requestChanges(Request $request, string $token): JsonResponse
    {
        $invite = $this->findValidInvite($token);

        if (!$invite) {
            return response()->json([
                'message' => 'Invalid or expired review link.',
            ], 404);
        }

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:2000'],
        ]);

        $approvalRequest = $invite->approvalRequest;
        $post = $approvalRequest->post;

        DB::transaction(function () use ($invite, $approvalRequest, $post, $validated) {
            // Store feedback in metadata (external reviewers don't have user accounts)
            $metadata = $post->metadata ?? [];
            $metadata['changes_requested_by_email'] = $invite->email;
            $metadata['changes_requested_at'] = now()->toIso8601String();
            $metadata['client_feedback'] = $validated['comment'];

            // Update approval request and post status
            $approvalRequest->update(['status' => 'rejected']);
            $post->update([
                'status' => 'changes_requested',
                'metadata' => $metadata,
            ]);

            // Mark invite as responded
            $invite->markResponded();
        });

        // Notify the post creator
        Mail::to($post->creator->email)->queue(
            new PostChangesRequestedMail($post, $invite->email, $validated['comment'])
        );

        return response()->json([
            'message' => 'Changes requested successfully.',
            'post_status' => 'changes_requested',
        ]);
    }
}
