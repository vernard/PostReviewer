<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'user_id',
        'type',
        'original_filename',
        'disk',
        'path',
        'mime_type',
        'size',
        'width',
        'height',
        'duration',
        'metadata',
        'thumbnails',
        'status',
    ];

    protected $casts = [
        'metadata' => 'array',
        'thumbnails' => 'array',
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'duration' => 'integer',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_media')
            ->withPivot(['position', 'platform_overrides'])
            ->withTimestamps();
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (empty($this->thumbnails)) {
            return null;
        }

        $thumbnail = $this->thumbnails['medium'] ?? $this->thumbnails['small'] ?? null;

        if ($thumbnail) {
            return Storage::disk($this->disk)->url($thumbnail);
        }

        return null;
    }

    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }
}
