<?php

namespace Database\Factories;

use App\Models\ApprovalRequest;
use App\Models\ApprovalResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApprovalResponse>
 */
class ApprovalResponseFactory extends Factory
{
    protected $model = ApprovalResponse::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'approval_request_id' => ApprovalRequest::factory(),
            'user_id' => User::factory(),
            'decision' => 'approved',
            'comment' => null,
        ];
    }

    /**
     * Set the approval request.
     */
    public function forApprovalRequest(ApprovalRequest $approvalRequest): static
    {
        return $this->state(fn(array $attributes) => [
            'approval_request_id' => $approvalRequest->id,
        ]);
    }

    /**
     * Set the reviewer.
     */
    public function reviewedBy(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Set the decision to approved.
     */
    public function approved(): static
    {
        return $this->state(fn(array $attributes) => [
            'decision' => 'approved',
        ]);
    }

    /**
     * Set the decision to changes requested.
     */
    public function changesRequested(): static
    {
        return $this->state(fn(array $attributes) => [
            'decision' => 'changes_requested',
        ]);
    }

    /**
     * Add a comment.
     */
    public function withComment(string $comment = null): static
    {
        return $this->state(fn(array $attributes) => [
            'comment' => $comment ?? fake()->sentence(),
        ]);
    }
}
