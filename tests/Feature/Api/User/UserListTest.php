<?php

namespace Tests\Feature\Api\User;

use App\Models\Agency;
use App\Models\Brand;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserListTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_agency_users(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();
        $manager = User::factory()->manager()->forAgency($agency)->create();
        $creator = User::factory()->creator()->forAgency($agency)->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/users');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'users' => [
                '*' => ['id', 'name', 'email', 'role', 'brands'],
            ],
            'pending_invitations',
        ]);
        $response->assertJsonCount(3, 'users');
    }

    public function test_manager_can_list_agency_users(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();
        $manager = User::factory()->manager()->forAgency($agency)->create();

        Sanctum::actingAs($manager);

        $response = $this->getJson('/api/users');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'users');
    }

    public function test_creator_cannot_list_users(): void
    {
        $agency = Agency::factory()->create();
        $creator = User::factory()->creator()->forAgency($agency)->create();

        Sanctum::actingAs($creator);

        $response = $this->getJson('/api/users');

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Only managers can view team members.',
        ]);
    }

    public function test_reviewer_cannot_list_users(): void
    {
        $agency = Agency::factory()->create();
        $reviewer = User::factory()->reviewer()->forAgency($agency)->create();

        Sanctum::actingAs($reviewer);

        $response = $this->getJson('/api/users');

        $response->assertStatus(403);
    }

    public function test_user_list_only_shows_same_agency_users(): void
    {
        $agency1 = Agency::factory()->create();
        $agency2 = Agency::factory()->create();

        $admin1 = User::factory()->admin()->forAgency($agency1)->create();
        $user1 = User::factory()->creator()->forAgency($agency1)->create();

        $admin2 = User::factory()->admin()->forAgency($agency2)->create();
        $user2 = User::factory()->creator()->forAgency($agency2)->create();

        Sanctum::actingAs($admin1);

        $response = $this->getJson('/api/users');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'users');

        // Verify only agency1 users are returned
        $userIds = collect($response->json('users'))->pluck('id')->all();
        $this->assertContains($admin1->id, $userIds);
        $this->assertContains($user1->id, $userIds);
        $this->assertNotContains($admin2->id, $userIds);
        $this->assertNotContains($user2->id, $userIds);
    }

    public function test_user_list_includes_user_brands(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();
        $creator = User::factory()->creator()->forAgency($agency)->create();

        $brand = Brand::factory()->forAgency($agency)->create();
        $brand->users()->attach($creator->id);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/users');

        $response->assertStatus(200);

        // Find creator in response and check brands
        $users = collect($response->json('users'));
        $creatorData = $users->firstWhere('id', $creator->id);
        $this->assertCount(1, $creatorData['brands']);
        $this->assertEquals($brand->id, $creatorData['brands'][0]['id']);
    }

    public function test_user_list_includes_pending_invitations(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();

        $invitation = Invitation::factory()
            ->forAgency($agency)
            ->invitedBy($admin)
            ->create(['email' => 'pending@example.com']);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/users');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'pending_invitations');
        $response->assertJsonPath('pending_invitations.0.email', 'pending@example.com');
    }

    public function test_user_list_excludes_expired_invitations(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();

        $expiredInvitation = Invitation::factory()
            ->forAgency($agency)
            ->invitedBy($admin)
            ->expired()
            ->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/users');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'pending_invitations');
    }

    public function test_user_list_excludes_accepted_invitations(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();

        $acceptedInvitation = Invitation::factory()
            ->forAgency($agency)
            ->invitedBy($admin)
            ->accepted()
            ->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/users');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'pending_invitations');
    }

    public function test_users_are_ordered_by_name(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create(['name' => 'Zebra']);
        User::factory()->forAgency($agency)->create(['name' => 'Alpha']);
        User::factory()->forAgency($agency)->create(['name' => 'Beta']);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/users');

        $response->assertStatus(200);
        $names = collect($response->json('users'))->pluck('name')->all();
        $this->assertEquals(['Alpha', 'Beta', 'Zebra'], $names);
    }
}
