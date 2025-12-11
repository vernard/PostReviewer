<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'creator',
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Set the user's agency.
     */
    public function forAgency(Agency $agency): static
    {
        return $this->state(fn(array $attributes) => [
            'agency_id' => $agency->id,
        ]);
    }

    /**
     * Set the user role to admin.
     */
    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'admin',
        ]);
    }

    /**
     * Set the user role to manager.
     */
    public function manager(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'manager',
        ]);
    }

    /**
     * Set the user role to creator.
     */
    public function creator(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'creator',
        ]);
    }

    /**
     * Set the user role to reviewer.
     */
    public function reviewer(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'reviewer',
        ]);
    }

    /**
     * Set a specific password.
     */
    public function withPassword(string $password): static
    {
        return $this->state(fn(array $attributes) => [
            'password' => Hash::make($password),
        ]);
    }

    /**
     * Set an avatar for the user.
     */
    public function withAvatar(): static
    {
        return $this->state(fn(array $attributes) => [
            'avatar' => 'avatars/' . fake()->uuid() . '.png',
        ]);
    }
}
