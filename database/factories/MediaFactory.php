<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'user_id' => User::factory(),
            'type' => 'image',
            'original_filename' => fake()->word() . '.jpg',
            'disk' => 'public',
            'path' => 'media/' . fake()->uuid() . '.jpg',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(100000, 5000000),
            'width' => 1920,
            'height' => 1080,
            'metadata' => [],
            'thumbnails' => [
                'small' => 'media/thumbs/small_' . fake()->uuid() . '.jpg',
                'medium' => 'media/thumbs/medium_' . fake()->uuid() . '.jpg',
                'large' => 'media/thumbs/large_' . fake()->uuid() . '.jpg',
            ],
            'status' => 'ready',
        ];
    }

    /**
     * Set the media's brand.
     */
    public function forBrand(Brand $brand): static
    {
        return $this->state(fn(array $attributes) => [
            'brand_id' => $brand->id,
        ]);
    }

    /**
     * Set the media's uploader.
     */
    public function uploadedBy(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Set the media type to image.
     */
    public function image(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'image',
            'original_filename' => fake()->word() . '.jpg',
            'path' => 'media/' . fake()->uuid() . '.jpg',
            'mime_type' => 'image/jpeg',
            'duration' => null,
        ]);
    }

    /**
     * Set the media type to video.
     */
    public function video(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'video',
            'original_filename' => fake()->word() . '.mp4',
            'path' => 'media/' . fake()->uuid() . '.mp4',
            'mime_type' => 'video/mp4',
            'duration' => fake()->numberBetween(5, 60),
            'thumbnails' => null,
        ]);
    }

    /**
     * Set the media status to processing.
     */
    public function processing(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'processing',
        ]);
    }

    /**
     * Set the media status to ready.
     */
    public function ready(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'ready',
        ]);
    }

    /**
     * Set the media status to failed.
     */
    public function failed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'failed',
        ]);
    }

    /**
     * Set PNG image type.
     */
    public function png(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'image',
            'original_filename' => fake()->word() . '.png',
            'path' => 'media/' . fake()->uuid() . '.png',
            'mime_type' => 'image/png',
        ]);
    }
}
