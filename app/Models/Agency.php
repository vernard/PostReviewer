<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Agency extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'settings',
        'storage_quota',
        'storage_used',
    ];

    protected $casts = [
        'settings' => 'array',
        'storage_quota' => 'integer',
        'storage_used' => 'integer',
    ];

    protected $appends = [
        'storage_used_formatted',
        'storage_quota_formatted',
        'storage_percentage',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($agency) {
            if (empty($agency->slug)) {
                $agency->slug = Str::slug($agency->name);
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function getStorageUsedFormattedAttribute(): string
    {
        return $this->formatBytes($this->storage_used);
    }

    public function getStorageQuotaFormattedAttribute(): string
    {
        return $this->formatBytes($this->storage_quota);
    }

    public function getStoragePercentageAttribute(): float
    {
        if ($this->storage_quota <= 0) {
            return 0;
        }
        return round(($this->storage_used / $this->storage_quota) * 100, 1);
    }

    public function hasStorageAvailable(int $bytes): bool
    {
        return ($this->storage_used + $bytes) <= $this->storage_quota;
    }

    public function incrementStorageUsed(int $bytes): void
    {
        $this->increment('storage_used', $bytes);
    }

    public function decrementStorageUsed(int $bytes): void
    {
        $newValue = max(0, $this->storage_used - $bytes);
        $this->update(['storage_used' => $newValue]);
    }

    public function recalculateStorageUsed(): int
    {
        $totalSize = Media::whereIn('brand_id', $this->brands()->pluck('id'))
            ->sum('size');

        $this->update(['storage_used' => $totalSize]);

        return $totalSize;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 1) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
