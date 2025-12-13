<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Collection extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'created_by',
        'name',
        'description',
        'approval_token',
        'approval_token_expires_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'approval_token_expires_at' => 'datetime',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Generate a secure approval token for public access
     */
    public function generateApprovalToken(int $expiresInDays = 30): string
    {
        $this->approval_token = Str::random(64);
        $this->approval_token_expires_at = now()->addDays($expiresInDays);
        $this->save();

        return $this->approval_token;
    }

    /**
     * Check if the approval token is valid
     */
    public function hasValidApprovalToken(): bool
    {
        return $this->approval_token
            && $this->approval_token_expires_at
            && $this->approval_token_expires_at->isFuture();
    }

    /**
     * Get the public approval URL
     */
    public function getApprovalUrl(): ?string
    {
        if (!$this->approval_token) {
            return null;
        }

        return url("/review/{$this->approval_token}");
    }

    /**
     * Get approval status summary
     */
    public function getStatusSummary(): array
    {
        $posts = $this->posts;

        return [
            'total' => $posts->count(),
            'draft' => $posts->where('status', 'draft')->count(),
            'pending_approval' => $posts->where('status', 'pending_approval')->count(),
            'changes_requested' => $posts->where('status', 'changes_requested')->count(),
            'approved' => $posts->where('status', 'approved')->count(),
        ];
    }

    /**
     * Check if all posts in the collection are approved
     */
    public function isFullyApproved(): bool
    {
        return $this->posts()->count() > 0
            && $this->posts()->where('status', '!=', 'approved')->count() === 0;
    }

    /**
     * Check if any posts need changes
     */
    public function hasPostsNeedingChanges(): bool
    {
        return $this->posts()->where('status', 'changes_requested')->count() > 0;
    }
}
