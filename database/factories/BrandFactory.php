<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'name' => fake()->company() . ' Brand',
            'description' => fake()->sentence(),
            'profile_name' => fake()->userName(),
            'color_scheme' => [
                'primary' => fake()->hexColor(),
                'secondary' => fake()->hexColor(),
            ],
            'settings' => [],
        ];
    }

    /**
     * Set the brand's agency.
     */
    public function forAgency(Agency $agency): static
    {
        return $this->state(fn(array $attributes) => [
            'agency_id' => $agency->id,
        ]);
    }

    /**
     * Indicate that the brand has a logo.
     */
    public function withLogo(): static
    {
        return $this->state(fn(array $attributes) => [
            'logo' => 'brands/logos/' . fake()->uuid() . '.png',
        ]);
    }

    /**
     * Indicate that the brand has a profile avatar.
     */
    public function withProfileAvatar(): static
    {
        return $this->state(fn(array $attributes) => [
            'profile_avatar' => 'brands/avatars/' . fake()->uuid() . '.png',
        ]);
    }
}
