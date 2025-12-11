<?php

namespace Database\Factories;

use App\Models\Agency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Agency>
 */
class AgencyFactory extends Factory
{
    protected $model = Agency::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'settings' => [],
        ];
    }

    /**
     * Indicate that the agency has a logo.
     */
    public function withLogo(): static
    {
        return $this->state(fn(array $attributes) => [
            'logo' => 'logos/' . fake()->uuid() . '.png',
        ]);
    }

    /**
     * Indicate that the agency has custom settings.
     */
    public function withSettings(array $settings): static
    {
        return $this->state(fn(array $attributes) => [
            'settings' => $settings,
        ]);
    }
}
