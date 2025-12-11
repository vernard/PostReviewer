<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'body' => fake()->paragraph(),
            'attachment' => null,
            'resolved' => false,
        ];
    }

    /**
     * Set the comment's post.
     */
    public function forPost(Post $post): static
    {
        return $this->state(fn(array $attributes) => [
            'post_id' => $post->id,
        ]);
    }

    /**
     * Set the comment's author.
     */
    public function by(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Set this comment as a reply to another comment.
     */
    public function replyTo(Comment $parent): static
    {
        return $this->state(fn(array $attributes) => [
            'parent_id' => $parent->id,
            'post_id' => $parent->post_id,
        ]);
    }

    /**
     * Mark the comment as resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn(array $attributes) => [
            'resolved' => true,
        ]);
    }

    /**
     * Mark the comment as unresolved.
     */
    public function unresolved(): static
    {
        return $this->state(fn(array $attributes) => [
            'resolved' => false,
        ]);
    }

    /**
     * Add an attachment to the comment.
     */
    public function withAttachment(string $attachment = null): static
    {
        return $this->state(fn(array $attributes) => [
            'attachment' => $attachment ?? 'comments/attachments/' . fake()->uuid() . '.png',
        ]);
    }

    /**
     * Set a custom body.
     */
    public function withBody(string $body): static
    {
        return $this->state(fn(array $attributes) => [
            'body' => $body,
        ]);
    }
}
