<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isManager()) {
            return response()->json([
                'message' => 'Only managers can view team members.',
            ], 403);
        }

        $users = User::where('agency_id', $user->agency_id)
            ->with('brands')
            ->orderBy('name')
            ->get();

        $pendingInvitations = Invitation::where('agency_id', $user->agency_id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->get();

        return response()->json([
            'users' => $users,
            'pending_invitations' => $pendingInvitations,
        ]);
    }

    public function invite(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isManager()) {
            return response()->json([
                'message' => 'Only managers can invite users.',
            ], 403);
        }

        $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'in:admin,manager,creator,reviewer'],
        ]);

        // Check if user already exists in this agency
        $existingUser = User::where('email', $request->email)
            ->where('agency_id', $user->agency_id)
            ->exists();

        if ($existingUser) {
            return response()->json([
                'message' => 'A user with this email already exists in your agency.',
            ], 422);
        }

        // Check for existing pending invitation
        $existingInvitation = Invitation::where('email', $request->email)
            ->where('agency_id', $user->agency_id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingInvitation) {
            return response()->json([
                'message' => 'An invitation has already been sent to this email.',
            ], 422);
        }

        // Create invitation
        $invitation = Invitation::create([
            'agency_id' => $user->agency_id,
            'invited_by' => $user->id,
            'email' => $request->email,
            'role' => $request->role,
        ]);

        // Send invitation email
        Mail::to($invitation->email)->queue(new InvitationMail($invitation));

        return response()->json([
            'invitation' => $invitation,
            'message' => 'Invitation sent successfully.',
        ], 201);
    }

    public function update(Request $request, User $targetUser): JsonResponse
    {
        $user = $request->user();

        if (!$user->isManager()) {
            return response()->json([
                'message' => 'Only managers can update users.',
            ], 403);
        }

        if ($targetUser->agency_id !== $user->agency_id) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        // Can't change own role
        if ($targetUser->id === $user->id && $request->has('role')) {
            return response()->json([
                'message' => 'You cannot change your own role.',
            ], 422);
        }

        // Only admins can promote to admin
        if ($request->role === 'admin' && !$user->isAdmin()) {
            return response()->json([
                'message' => 'Only admins can promote users to admin.',
            ], 403);
        }

        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'role' => ['sometimes', 'in:admin,manager,creator,reviewer'],
        ]);

        $targetUser->update($request->only(['name', 'role']));

        return response()->json([
            'user' => $targetUser->fresh('brands'),
        ]);
    }

    public function destroy(Request $request, User $targetUser): JsonResponse
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'Only admins can remove users.',
            ], 403);
        }

        if ($targetUser->agency_id !== $user->agency_id) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        if ($targetUser->id === $user->id) {
            return response()->json([
                'message' => 'You cannot remove yourself.',
            ], 422);
        }

        $targetUser->delete();

        return response()->json([
            'message' => 'User removed successfully.',
        ]);
    }
}
