<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'brand_id',
        'created_by',
        'title',
        'caption',
        'platforms',
        'status',
        'scheduled_for',
        'metadata',
    ];

    protected $casts = [
        'platforms' => 'array',
        'metadata' => 'array',
        'scheduled_for' => 'datetime',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'post_media')
            ->withPivot(['position', 'platform_overrides'])
            ->withTimestamps()
            ->orderBy('post_media.position');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function approvalRequests(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class);
    }

    public function latestApprovalRequest(): HasOne
    {
        return $this->hasOne(ApprovalRequest::class)->latestOfMany();
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPendingApproval(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function needsChanges(): bool
    {
        return $this->status === 'changes_requested';
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'changes_requested']);
    }

    public function canBeSubmittedForApproval(): bool
    {
        return in_array($this->status, ['draft', 'changes_requested']);
    }
}
