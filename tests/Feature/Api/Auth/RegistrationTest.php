<?php

namespace Tests\Feature\Api\Auth;

use App\Models\Agency;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'agency_name' => 'Test Agency',
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

        // Verify agency was created
        $this->assertDatabaseHas('agencies', [
            'name' => 'Test Agency',
        ]);

        // Verify user was created with admin role
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'admin',
        ]);

        // Verify initial brand was created
        $this->assertDatabaseHas('brands', [
            'name' => 'Test Agency',
        ]);

        // Verify user is attached to the brand
        $user = User::where('email', 'john@example.com')->first();
        $this->assertEquals(1, $user->brands()->count());
    }

    public function test_registration_creates_agency_with_slug(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'agency_name' => 'My Test Agency',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201);

        $agency = Agency::where('name', 'My Test Agency')->first();
        $this->assertNotNull($agency->slug);
        $this->assertEquals('my-test-agency', $agency->slug);
    }

    public function test_registration_fails_with_missing_name(): void
    {
        $response = $this->postJson('/api/register', [
            'email' => 'john@example.com',
            'agency_name' => 'Test Agency',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
    }

    public function test_registration_fails_with_missing_email(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'agency_name' => 'Test Agency',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    public function test_registration_fails_with_invalid_email(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'not-an-email',
            'agency_name' => 'Test Agency',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        // Create existing user
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'agency_name' => 'Test Agency',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    public function test_registration_fails_with_missing_agency_name(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('agency_name');
    }

    public function test_registration_fails_with_missing_password(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'agency_name' => 'Test Agency',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');
    }

    public function test_registration_fails_with_mismatched_password_confirmation(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'agency_name' => 'Test Agency',
            'password' => 'Password123!',
            'password_confirmation' => 'DifferentPassword123!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');
    }

    public function test_registration_returns_valid_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'agency_name' => 'Test Agency',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201);
        $token = $response->json('token');

        // Use the token to make an authenticated request
        $userResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user');

        $userResponse->assertStatus(200);
        $userResponse->assertJsonPath('user.email', 'john@example.com');
    }
}
