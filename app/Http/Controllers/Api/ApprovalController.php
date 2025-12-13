<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PostApprovedMail;
use App\Mail\PostChangesRequestedMail;
use App\Models\ApprovalRequest;
use App\Models\ApprovalResponse;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ApprovalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->canReview()) {
            return response()->json([
                'message' => 'You do not have permission to review posts.',
            ], 403);
        }

        // Get brand IDs the user has access to
        if ($user->isManager()) {
            $brandIds = $user->agency->brands()->pluck('id');
        } else {
            $brandIds = $user->brands()->pluck('brands.id');
        }

        $query = ApprovalRequest::with(['post.brand', 'post.creator', 'post.media', 'requester', 'responses.user'])
            ->whereHas('post', function ($q) use ($brandIds) {
                $q->whereIn('brand_id', $brandIds);
            });

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            // Default to pending
            $query->where('status', 'pending');
        }

        $approvals = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json($approvals);
    }

    public function approve(Request $request, ApprovalRequest $approvalRequest): JsonResponse
    {
        $user = $request->user();

        if (!$user->canReview()) {
            return response()->json([
                'message' => 'You do not have permission to approve posts.',
            ], 403);
        }

        $post = $approvalRequest->post;

        if (!$user->hasBrandAccess($post->brand)) {
            return response()->json([
                'message' => 'You do not have access to this brand.',
            ], 403);
        }

        if (!$approvalRequest->isPending()) {
            return response()->json([
                'message' => 'This approval request has already been processed.',
            ], 422);
        }

        $request->validate([
            'comment' => ['nullable', 'string'],
        ]);

        // Create approval response
        ApprovalResponse::create([
            'approval_request_id' => $approvalRequest->id,
            'user_id' => $user->id,
            'decision' => 'approved',
            'comment' => $request->comment,
        ]);

        // Update approval request status
        $approvalRequest->update(['status' => 'approved']);

        // Update post status
        $post->update(['status' => 'approved']);

        // Notify the post creator
        Mail::to($post->creator->email)->queue(new PostApprovedMail($post, $user->name));

        return response()->json([
            'approval_request' => $approvalRequest->fresh(['responses.user']),
            'post' => $post->fresh(),
            'message' => 'Post approved successfully.',
        ]);
    }

    public function requestChanges(Request $request, ApprovalRequest $approvalRequest): JsonResponse
    {
        $user = $request->user();

        if (!$user->canReview()) {
            return response()->json([
                'message' => 'You do not have permission to review posts.',
            ], 403);
        }

        $post = $approvalRequest->post;

        if (!$user->hasBrandAccess($post->brand)) {
            return response()->json([
                'message' => 'You do not have access to this brand.',
            ], 403);
        }

        if (!$approvalRequest->isPending()) {
            return response()->json([
                'message' => 'This approval request has already been processed.',
            ], 422);
        }

        $request->validate([
            'comment' => ['required', 'string'],
        ]);

        // Create approval response
        ApprovalResponse::create([
            'approval_request_id' => $approvalRequest->id,
            'user_id' => $user->id,
            'decision' => 'changes_requested',
            'comment' => $request->comment,
        ]);

        // Update approval request status
        $approvalRequest->update(['status' => 'rejected']);

        // Update post status
        $post->update(['status' => 'changes_requested']);

        // Notify the post creator
        Mail::to($post->creator->email)->queue(
            new PostChangesRequestedMail($post, $user->name, $request->comment)
        );

        return response()->json([
            'approval_request' => $approvalRequest->fresh(['responses.user']),
            'post' => $post->fresh(),
            'message' => 'Changes requested.',
        ]);
    }
}
