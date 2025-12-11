<?php

namespace Tests\Feature\Api\User;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_user_name(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();
        $creator = User::factory()->creator()->forAgency($agency)->create(['name' => 'Old Name']);

        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/users/{$creator->id}", [
            'name' => 'New Name',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('user.name', 'New Name');

        $this->assertDatabaseHas('users', [
            'id' => $creator->id,
            'name' => 'New Name',
        ]);
    }

    public function test_admin_can_update_user_role(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();
        $creator = User::factory()->creator()->forAgency($agency)->create();

        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/users/{$creator->id}", [
            'role' => 'manager',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('user.role', 'manager');
    }

    public function test_admin_can_promote_to_admin(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();
        $manager = User::factory()->manager()->forAgency($agency)->create();

        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/users/{$manager->id}", [
            'role' => 'admin',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('user.role', 'admin');
    }

    public function test_manager_can_update_user_name(): void
    {
        $agency = Agency::factory()->create();
        $manager = User::factory()->manager()->forAgency($agency)->create();
        $creator = User::factory()->creator()->forAgency($agency)->create();

        Sanctum::actingAs($manager);

        $response = $this->putJson("/api/users/{$creator->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('user.name', 'Updated Name');
    }

    public function test_manager_cannot_promote_to_admin(): void
    {
        $agency = Agency::factory()->create();
        $manager = User::factory()->manager()->forAgency($agency)->create();
        $creator = User::factory()->creator()->forAgency($agency)->create();

        Sanctum::actingAs($manager);

        $response = $this->putJson("/api/users/{$creator->id}", [
            'role' => 'admin',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Only admins can promote users to admin.',
        ]);
    }

    public function test_manager_can_change_role_to_non_admin(): void
    {
        $agency = Agency::factory()->create();
        $manager = User::factory()->manager()->forAgency($agency)->create();
        $creator = User::factory()->creator()->forAgency($agency)->create();

        Sanctum::actingAs($manager);

        $response = $this->putJson("/api/users/{$creator->id}", [
            'role' => 'reviewer',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('user.role', 'reviewer');
    }

    public function test_creator_cannot_update_users(): void
    {
        $agency = Agency::factory()->create();
        $creator1 = User::factory()->creator()->forAgency($agency)->create();
        $creator2 = User::factory()->creator()->forAgency($agency)->create();

        Sanctum::actingAs($creator1);

        $response = $this->putJson("/api/users/{$creator2->id}", [
            'name' => 'New Name',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Only managers can update users.',
        ]);
    }

    public function test_reviewer_cannot_update_users(): void
    {
        $agency = Agency::factory()->create();
        $reviewer = User::factory()->reviewer()->forAgency($agency)->create();
        $creator = User::factory()->creator()->forAgency($agency)->create();

        Sanctum::actingAs($reviewer);

        $response = $this->putJson("/api/users/{$creator->id}", [
            'name' => 'New Name',
        ]);

        $response->assertStatus(403);
    }

    public function test_cannot_update_user_from_different_agency(): void
    {
        $agency1 = Agency::factory()->create();
        $agency2 = Agency::factory()->create();

        $admin1 = User::factory()->admin()->forAgency($agency1)->create();
        $user2 = User::factory()->creator()->forAgency($agency2)->create();

        Sanctum::actingAs($admin1);

        $response = $this->putJson("/api/users/{$user2->id}", [
            'name' => 'New Name',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'User not found.',
        ]);
    }

    public function test_cannot_change_own_role(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();

        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/users/{$admin->id}", [
            'role' => 'manager',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'You cannot change your own role.',
        ]);
    }

    public function test_can_update_own_name(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create(['name' => 'Old Name']);

        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/users/{$admin->id}", [
            'name' => 'New Name',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('user.name', 'New Name');
    }

    public function test_update_returns_user_with_brands(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();
        $creator = User::factory()->creator()->forAgency($agency)->create();

        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/users/{$creator->id}", [
            'name' => 'Updated',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user' => [
                'id',
                'name',
                'email',
                'role',
                'brands',
            ],
        ]);
    }

    public function test_update_validates_role_values(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();
        $creator = User::factory()->creator()->forAgency($agency)->create();

        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/users/{$creator->id}", [
            'role' => 'invalid_role',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('role');
    }

    public function test_update_validates_name_max_length(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();
        $creator = User::factory()->creator()->forAgency($agency)->create();

        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/users/{$creator->id}", [
            'name' => str_repeat('a', 256),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
    }
}
