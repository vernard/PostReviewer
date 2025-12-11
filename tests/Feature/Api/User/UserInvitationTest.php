<?php

namespace Tests\Feature\Api\User;

use App\Models\Agency;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_invite_user(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users/invite', [
            'email' => 'newuser@example.com',
            'role' => 'creator',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'invitation' => [
                'id',
                'email',
                'role',
                'token',
            ],
            'message',
        ]);

        $this->assertDatabaseHas('invitations', [
            'email' => 'newuser@example.com',
            'role' => 'creator',
            'agency_id' => $agency->id,
            'invited_by' => $admin->id,
        ]);
    }

    public function test_manager_can_invite_user(): void
    {
        $agency = Agency::factory()->create();
        $manager = User::factory()->manager()->forAgency($agency)->create();

        Sanctum::actingAs($manager);

        $response = $this->postJson('/api/users/invite', [
            'email' => 'newuser@example.com',
            'role' => 'reviewer',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('invitations', [
            'email' => 'newuser@example.com',
            'role' => 'reviewer',
        ]);
    }

    public function test_creator_cannot_invite_user(): void
    {
        $agency = Agency::factory()->create();
        $creator = User::factory()->creator()->forAgency($agency)->create();

        Sanctum::actingAs($creator);

        $response = $this->postJson('/api/users/invite', [
            'email' => 'newuser@example.com',
            'role' => 'creator',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Only managers can invite users.',
        ]);
    }

    public function test_reviewer_cannot_invite_user(): void
    {
        $agency = Agency::factory()->create();
        $reviewer = User::factory()->reviewer()->forAgency($agency)->create();

        Sanctum::actingAs($reviewer);

        $response = $this->postJson('/api/users/invite', [
            'email' => 'newuser@example.com',
            'role' => 'creator',
        ]);

        $response->assertStatus(403);
    }

    public function test_cannot_invite_existing_agency_user(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();
        $existingUser = User::factory()->forAgency($agency)->create([
            'email' => 'existing@example.com',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users/invite', [
            'email' => 'existing@example.com',
            'role' => 'creator',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'A user with this email already exists in your agency.',
        ]);
    }

    public function test_cannot_invite_user_with_pending_invitation(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();

        // Create pending invitation
        Invitation::factory()
            ->forAgency($agency)
            ->invitedBy($admin)
            ->create(['email' => 'pending@example.com']);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users/invite', [
            'email' => 'pending@example.com',
            'role' => 'creator',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'An invitation has already been sent to this email.',
        ]);
    }

    public function test_can_reinvite_user_with_expired_invitation(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();

        // Create expired invitation
        Invitation::factory()
            ->forAgency($agency)
            ->invitedBy($admin)
            ->expired()
            ->create(['email' => 'expired@example.com']);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users/invite', [
            'email' => 'expired@example.com',
            'role' => 'creator',
        ]);

        $response->assertStatus(201);
    }

    public function test_can_reinvite_user_with_accepted_invitation(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();

        // Create accepted invitation (but user might have been removed)
        Invitation::factory()
            ->forAgency($agency)
            ->invitedBy($admin)
            ->accepted()
            ->create(['email' => 'accepted@example.com']);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users/invite', [
            'email' => 'accepted@example.com',
            'role' => 'creator',
        ]);

        $response->assertStatus(201);
    }

    public function test_invitation_requires_email(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users/invite', [
            'role' => 'creator',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    public function test_invitation_requires_valid_email(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users/invite', [
            'email' => 'not-an-email',
            'role' => 'creator',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    public function test_invitation_requires_role(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users/invite', [
            'email' => 'newuser@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('role');
    }

    public function test_invitation_requires_valid_role(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users/invite', [
            'email' => 'newuser@example.com',
            'role' => 'invalid_role',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('role');
    }

    public function test_can_invite_user_as_admin(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users/invite', [
            'email' => 'newadmin@example.com',
            'role' => 'admin',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('invitations', [
            'email' => 'newadmin@example.com',
            'role' => 'admin',
        ]);
    }

    public function test_invitation_belongs_to_correct_agency(): void
    {
        $agency1 = Agency::factory()->create();
        $agency2 = Agency::factory()->create();

        $admin1 = User::factory()->admin()->forAgency($agency1)->create();

        Sanctum::actingAs($admin1);

        $response = $this->postJson('/api/users/invite', [
            'email' => 'newuser@example.com',
            'role' => 'creator',
        ]);

        $response->assertStatus(201);

        $invitation = Invitation::where('email', 'newuser@example.com')->first();
        $this->assertEquals($agency1->id, $invitation->agency_id);
        $this->assertNotEquals($agency2->id, $invitation->agency_id);
    }
}
