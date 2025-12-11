<?php

namespace Database\Factories;

use App\Models\ApprovalRequest;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApprovalRequest>
 */
class ApprovalRequestFactory extends Factory
{
    protected $model = ApprovalRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'requested_by' => User::factory(),
            'status' => 'pending',
            'due_date' => now()->addDays(3),
        ];
    }

    /**
     * Set the approval request's post.
     */
    public function forPost(Post $post): static
    {
        return $this->state(fn(array $attributes) => [
            'post_id' => $post->id,
        ]);
    }

    /**
     * Set the requester.
     */
    public function requestedBy(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'requested_by' => $user->id,
        ]);
    }

    /**
     * Set the status to pending.
     */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Set the status to approved.
     */
    public function approved(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'approved',
        ]);
    }

    /**
     * Set the status to rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'rejected',
        ]);
    }

    /**
     * Set a custom due date.
     */
    public function dueOn(\DateTime $date): static
    {
        return $this->state(fn(array $attributes) => [
            'due_date' => $date,
        ]);
    }

    /**
     * Set the due date to be overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn(array $attributes) => [
            'due_date' => now()->subDay(),
        ]);
    }

    /**
     * Remove the due date.
     */
    public function withoutDueDate(): static
    {
        return $this->state(fn(array $attributes) => [
            'due_date' => null,
        ]);
    }
}
