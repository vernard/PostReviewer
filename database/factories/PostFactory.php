<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'created_by' => User::factory(),
            'title' => fake()->sentence(4),
            'caption' => fake()->paragraph(),
            'platforms' => ['instagram_feed'],
            'status' => 'draft',
            'metadata' => [],
        ];
    }

    /**
     * Set the post's brand.
     */
    public function forBrand(Brand $brand): static
    {
        return $this->state(fn(array $attributes) => [
            'brand_id' => $brand->id,
        ]);
    }

    /**
     * Set the post's creator.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'created_by' => $user->id,
        ]);
    }

    /**
     * Set the post status to draft.
     */
    public function draft(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Set the post status to pending approval.
     */
    public function pendingApproval(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending_approval',
        ]);
    }

    /**
     * Set the post status to approved.
     */
    public function approved(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'approved',
        ]);
    }

    /**
     * Set the post status to changes requested.
     */
    public function changesRequested(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'changes_requested',
        ]);
    }

    /**
     * Set the post platforms.
     */
    public function forPlatforms(array $platforms): static
    {
        return $this->state(fn(array $attributes) => [
            'platforms' => $platforms,
        ]);
    }

    /**
     * Set the post to be scheduled.
     */
    public function scheduled(\DateTime $date = null): static
    {
        return $this->state(fn(array $attributes) => [
            'scheduled_for' => $date ?? now()->addDays(7),
        ]);
    }

    /**
     * Set all social media platforms.
     */
    public function allPlatforms(): static
    {
        return $this->state(fn(array $attributes) => [
            'platforms' => [
                'facebook_feed',
                'facebook_story',
                'instagram_feed',
                'instagram_story',
                'instagram_reel',
            ],
        ]);
    }
}
