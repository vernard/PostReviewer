<?php

namespace Tests\Traits;

use App\Models\Agency;
use App\Models\ApprovalRequest;
use App\Models\Brand;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

trait TestHelpers
{
    use RefreshDatabase;

    protected Agency $agency;
    protected Brand $brand;
    protected User $admin;
    protected User $manager;
    protected User $creator;
    protected User $reviewer;

    /**
     * Create an agency with an admin user.
     */
    protected function createAgencyWithAdmin(): array
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->forAgency($agency)->create();

        return compact('agency', 'admin');
    }

    /**
     * Create a full test environment with agency, brand, and users of all roles.
     */
    protected function createFullTestEnvironment(): void
    {
        $this->agency = Agency::factory()->create();
        $this->brand = Brand::factory()->forAgency($this->agency)->create();

        $this->admin = User::factory()->admin()->forAgency($this->agency)->create();
        $this->manager = User::factory()->manager()->forAgency($this->agency)->create();
        $this->creator = User::factory()->creator()->forAgency($this->agency)->create();
        $this->reviewer = User::factory()->reviewer()->forAgency($this->agency)->create();

        // Assign creator and reviewer to the brand
        $this->brand->users()->attach([$this->creator->id, $this->reviewer->id]);
    }

    /**
     * Create and authenticate a user with the specified role.
     */
    protected function actingAsRole(string $role, ?Brand $brand = null): User
    {
        $user = User::factory()->{$role}()->forAgency($this->agency ?? Agency::factory()->create())->create();

        if ($brand && in_array($role, ['creator', 'reviewer'])) {
            $brand->users()->attach($user->id);
        }

        $this->actingAs($user, 'sanctum');

        return $user;
    }

    /**
     * Authenticate as a specific user via Sanctum.
     */
    protected function actingAsUser(User $user): self
    {
        $this->actingAs($user, 'sanctum');

        return $this;
    }

    /**
     * Create a post with media attached.
     */
    protected function createPostWithMedia(Brand $brand, User $creator, int $mediaCount = 1): Post
    {
        $post = Post::factory()
            ->forBrand($brand)
            ->createdBy($creator)
            ->create();

        $mediaItems = Media::factory()
            ->count($mediaCount)
            ->forBrand($brand)
            ->uploadedBy($creator)
            ->create();

        foreach ($mediaItems as $index => $media) {
            $post->media()->attach($media->id, ['position' => $index]);
        }

        return $post->fresh(['media']);
    }

    /**
     * Create a complete approval workflow: post + approval request.
     */
    protected function createApprovalWorkflow(Brand $brand, User $creator): array
    {
        $post = Post::factory()
            ->forBrand($brand)
            ->createdBy($creator)
            ->pendingApproval()
            ->create();

        $approvalRequest = ApprovalRequest::factory()
            ->forPost($post)
            ->requestedBy($creator)
            ->pending()
            ->create();

        return compact('post', 'approvalRequest');
    }

    /**
     * Create a draft post.
     */
    protected function createDraftPost(Brand $brand, User $creator): Post
    {
        return Post::factory()
            ->forBrand($brand)
            ->createdBy($creator)
            ->draft()
            ->create();
    }

    /**
     * Create media for a brand.
     */
    protected function createMedia(Brand $brand, User $user, string $type = 'image'): Media
    {
        return Media::factory()
            ->forBrand($brand)
            ->uploadedBy($user)
            ->{$type}()
            ->create();
    }

    /**
     * Get the API headers for authenticated requests.
     */
    protected function apiHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Assert that the response has the given validation errors.
     */
    protected function assertValidationError($response, string $field): void
    {
        $response->assertStatus(422);
        $response->assertJsonValidationErrors($field);
    }

    /**
     * Assert successful JSON response.
     */
    protected function assertSuccessResponse($response, int $status = 200): void
    {
        $response->assertStatus($status);
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Create another agency with its own users (for testing agency isolation).
     */
    protected function createOtherAgency(): array
    {
        $otherAgency = Agency::factory()->create();
        $otherBrand = Brand::factory()->forAgency($otherAgency)->create();
        $otherUser = User::factory()->admin()->forAgency($otherAgency)->create();

        return compact('otherAgency', 'otherBrand', 'otherUser');
    }

    /**
     * Assert that a user cannot access another agency's resource.
     */
    protected function assertCannotAccessOtherAgencyResource($response): void
    {
        $response->assertStatus(403);
    }

    /**
     * Assert that a resource was not found.
     */
    protected function assertResourceNotFound($response): void
    {
        $response->assertStatus(404);
    }
}
