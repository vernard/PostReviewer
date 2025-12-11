<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invitation>
 */
class InvitationFactory extends Factory
{
    protected $model = Invitation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'invited_by' => User::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role' => 'creator',
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
        ];
    }

    /**
     * Set the invitation's agency.
     */
    public function forAgency(Agency $agency): static
    {
        return $this->state(fn(array $attributes) => [
            'agency_id' => $agency->id,
        ]);
    }

    /**
     * Set the inviter.
     */
    public function invitedBy(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'invited_by' => $user->id,
            'agency_id' => $user->agency_id,
        ]);
    }

    /**
     * Set the role to admin.
     */
    public function asAdmin(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'admin',
        ]);
    }

    /**
     * Set the role to manager.
     */
    public function asManager(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'manager',
        ]);
    }

    /**
     * Set the role to creator.
     */
    public function asCreator(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'creator',
        ]);
    }

    /**
     * Set the role to reviewer.
     */
    public function asReviewer(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'reviewer',
        ]);
    }

    /**
     * Mark the invitation as expired.
     */
    public function expired(): static
    {
        return $this->state(fn(array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * Mark the invitation as accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn(array $attributes) => [
            'accepted_at' => now(),
        ]);
    }

    /**
     * Set a specific token.
     */
    public function withToken(string $token): static
    {
        return $this->state(fn(array $attributes) => [
            'token' => $token,
        ]);
    }

    /**
     * Set a specific email.
     */
    public function forEmail(string $email): static
    {
        return $this->state(fn(array $attributes) => [
            'email' => $email,
        ]);
    }
}
