<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApprovalInvite extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_request_id',
        'email',
        'token',
        'expires_at',
        'responded_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invite) {
            if (empty($invite->token)) {
                $invite->token = Str::random(64);
            }
            if (empty($invite->expires_at)) {
                $invite->expires_at = now()->addDays(7);
            }
        });
    }

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return !$this->isExpired() && $this->approvalRequest->isPending();
    }

    public function hasResponded(): bool
    {
        return $this->responded_at !== null;
    }

    public function markResponded(): void
    {
        $this->update(['responded_at' => now()]);
    }

    /**
     * Get the post through the approval request.
     */
    public function getPostAttribute(): ?Post
    {
        return $this->approvalRequest?->post;
    }

    /**
     * Generate the public review URL.
     */
    public function getReviewUrlAttribute(): string
    {
        return config('app.url') . '/post-review/' . $this->token;
    }
}
