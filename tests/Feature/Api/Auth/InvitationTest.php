<?php

namespace Tests\Feature\Api\Auth;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_accept_valid_invitation(): void
    {
        $invitation = Invitation::factory()->asCreator()->create([
            'email' => 'invited@example.com',
        ]);

        $response = $this->postJson("/api/invitation/{$invitation->token}/accept", [
            'name' => 'Invited User',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'user' => [
                'id',
                'name',
                'email',
                'role',
                'agency' => [
                    'id',
                    'name',
                ],
            ],
            'token',
        ]);

        // Verify user was created with correct role
        $this->assertDatabaseHas('users', [
            'name' => 'Invited User',
            'email' => 'invited@example.com',
            'role' => 'creator',
            'agency_id' => $invitation->agency_id,
        ]);

        // Verify invitation was marked as accepted
        $invitation->refresh();
        $this->assertNotNull($invitation->accepted_at);
    }

    public function test_user_is_created_with_invitation_role(): void
    {
        $invitation = Invitation::factory()->asManager()->create([
            'email' => 'manager@example.com',
        ]);

        $response = $this->postJson("/api/invitation/{$invitation->token}/accept", [
            'name' => 'New Manager',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('user.role', 'manager');
    }

    public function test_cannot_accept_expired_invitation(): void
    {
        $invitation = Invitation::factory()->expired()->create([
            'email' => 'expired@example.com',
        ]);

        $response = $this->postJson("/api/invitation/{$invitation->token}/accept", [
            'name' => 'Expired User',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'This invitation is no longer valid.',
        ]);

        // User should not be created
        $this->assertDatabaseMissing('users', [
            'email' => 'expired@example.com',
        ]);
    }

    public function test_cannot_accept_already_accepted_invitation(): void
    {
        $invitation = Invitation::factory()->accepted()->create([
            'email' => 'accepted@example.com',
        ]);

        $response = $this->postJson("/api/invitation/{$invitation->token}/accept", [
            'name' => 'Already Accepted',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'This invitation is no longer valid.',
        ]);
    }

    public function test_cannot_accept_invitation_with_invalid_token(): void
    {
        $response = $this->postJson('/api/invitation/invalid-token/accept', [
            'name' => 'Invalid Token',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(404);
    }

    public function test_invitation_acceptance_requires_name(): void
    {
        $invitation = Invitation::factory()->create();

        $response = $this->postJson("/api/invitation/{$invitation->token}/accept", [
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
    }

    public function test_invitation_acceptance_requires_password(): void
    {
        $invitation = Invitation::factory()->create();

        $response = $this->postJson("/api/invitation/{$invitation->token}/accept", [
            'name' => 'Test User',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');
    }

    public function test_invitation_acceptance_requires_password_confirmation(): void
    {
        $invitation = Invitation::factory()->create();

        $response = $this->postJson("/api/invitation/{$invitation->token}/accept", [
            'name' => 'Test User',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');
    }

    public function test_invitation_acceptance_returns_valid_token(): void
    {
        $invitation = Invitation::factory()->create([
            'email' => 'token@example.com',
        ]);

        $response = $this->postJson("/api/invitation/{$invitation->token}/accept", [
            'name' => 'Token User',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201);
        $token = $response->json('token');

        // Use the token to make an authenticated request
        $userResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user');

        $userResponse->assertStatus(200);
        $userResponse->assertJsonPath('user.email', 'token@example.com');
    }

    public function test_user_belongs_to_invitation_agency(): void
    {
        $invitation = Invitation::factory()->create([
            'email' => 'agency@example.com',
        ]);

        $response = $this->postJson("/api/invitation/{$invitation->token}/accept", [
            'name' => 'Agency User',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'agency@example.com')->first();
        $this->assertEquals($invitation->agency_id, $user->agency_id);
    }
}
