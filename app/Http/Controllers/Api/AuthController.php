<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Brand;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'agency_name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        // Create agency
        $agency = Agency::create([
            'name' => $request->agency_name,
        ]);

        // Create user as admin of the agency
        $user = User::create([
            'agency_id' => $agency->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin',
        ]);

        // Add user to agency pivot table
        $user->agencies()->attach($agency->id, ['role' => 'admin']);

        // Create the first brand for the agency
        $brand = Brand::create([
            'agency_id' => $agency->id,
            'name' => $request->agency_name,
            'logo' => $request->hasFile('logo')
                ? $request->file('logo')->store('brands', 'public')
                : null,
        ]);

        // Attach user to the brand
        $brand->users()->attach($user->id);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('agency'),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('agency'),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        // Only delete if it's a real PersonalAccessToken (API token auth)
        // TransientToken is used for SPA/cookie auth and doesn't need deletion
        if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $token->delete();
        }

        // For SPA authentication, also clear the session if available
        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->load('agency', 'brands', 'agencies'),
        ]);
    }

    public function switchAgency(Request $request): JsonResponse
    {
        $request->validate([
            'agency_id' => ['required', 'exists:agencies,id'],
        ]);

        $user = $request->user();
        $agencyId = $request->agency_id;

        // Check if user belongs to this agency
        if (!$user->agencies()->where('agency_id', $agencyId)->exists()) {
            return response()->json([
                'message' => 'You do not have access to this workspace.',
            ], 403);
        }

        // Update user's current agency
        $user->update(['agency_id' => $agencyId]);

        return response()->json([
            'user' => $user->fresh()->load('agency', 'brands', 'agencies'),
            'message' => 'Workspace switched successfully.',
        ]);
    }

    public function acceptInvitation(Request $request, string $token): JsonResponse
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if (!$invitation->isValid()) {
            return response()->json([
                'message' => 'This invitation is no longer valid.',
            ], 422);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Create user
        $user = User::create([
            'agency_id' => $invitation->agency_id,
            'name' => $request->name,
            'email' => $invitation->email,
            'password' => Hash::make($request->password),
            'role' => $invitation->role,
        ]);

        // Add user to agency pivot table
        $user->agencies()->attach($invitation->agency_id, ['role' => $invitation->role]);

        // Mark invitation as accepted
        $invitation->update(['accepted_at' => now()]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('agency'),
            'token' => $token,
        ], 201);
    }
}
