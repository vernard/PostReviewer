<?php

namespace Tests\Feature\Api\User;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_user(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();
        $creator = User::factory()->creator()->forAgency($agency)->create();

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/users/{$creator->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'User removed successfully.',
        ]);

        $this->assertDatabaseMissing('users', [
            'id' => $creator->id,
        ]);
    }

    public function test_manager_cannot_delete_user(): void
    {
        $agency = Agency::factory()->create();
        $manager = User::factory()->manager()->forAgency($agency)->create();
        $creator = User::factory()->creator()->forAgency($agency)->create();

        Sanctum::actingAs($manager);

        $response = $this->deleteJson("/api/users/{$creator->id}");

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Only admins can remove users.',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $creator->id,
        ]);
    }

    public function test_creator_cannot_delete_user(): void
    {
        $agency = Agency::factory()->create();
        $creator1 = User::factory()->creator()->forAgency($agency)->create();
        $creator2 = User::factory()->creator()->forAgency($agency)->create();

        Sanctum::actingAs($creator1);

        $response = $this->deleteJson("/api/users/{$creator2->id}");

        $response->assertStatus(403);
    }

    public function test_reviewer_cannot_delete_user(): void
    {
        $agency = Agency::factory()->create();
        $reviewer = User::factory()->reviewer()->forAgency($agency)->create();
        $creator = User::factory()->creator()->forAgency($agency)->create();

        Sanctum::actingAs($reviewer);

        $response = $this->deleteJson("/api/users/{$creator->id}");

        $response->assertStatus(403);
    }

    public function test_cannot_delete_self(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/users/{$admin->id}");

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'You cannot remove yourself.',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);
    }

    public function test_cannot_delete_user_from_different_agency(): void
    {
        $agency1 = Agency::factory()->create();
        $agency2 = Agency::factory()->create();

        $admin1 = User::factory()->admin()->forAgency($agency1)->create();
        $user2 = User::factory()->creator()->forAgency($agency2)->create();

        Sanctum::actingAs($admin1);

        $response = $this->deleteJson("/api/users/{$user2->id}");

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'User not found.',
        ]);

        // User should still exist
        $this->assertDatabaseHas('users', [
            'id' => $user2->id,
        ]);
    }

    public function test_admin_can_delete_another_admin(): void
    {
        $agency = Agency::factory()->create();
        $admin1 = User::factory()->admin()->forAgency($agency)->create();
        $admin2 = User::factory()->admin()->forAgency($agency)->create();

        Sanctum::actingAs($admin1);

        $response = $this->deleteJson("/api/users/{$admin2->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', [
            'id' => $admin2->id,
        ]);
    }

    public function test_admin_can_delete_manager(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();
        $manager = User::factory()->manager()->forAgency($agency)->create();

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/users/{$manager->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', [
            'id' => $manager->id,
        ]);
    }

    public function test_delete_nonexistent_user_returns_404(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();

        Sanctum::actingAs($admin);

        $response = $this->deleteJson('/api/users/99999');

        $response->assertStatus(404);
    }
}
