<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'name',
        'slug',
        'description',
        'logo',
        'logo_flat',
        'color_scheme',
        'profile_name',
        'profile_avatar',
        'instagram_handle',
        'facebook_page_name',
        'settings',
        'default_reviewers',
    ];

    protected $casts = [
        'color_scheme' => 'array',
        'settings' => 'array',
        'default_reviewers' => 'array',
    ];

    protected $appends = ['logo_url', 'logo_flat_url'];

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }

    public function getLogoFlatUrlAttribute(): ?string
    {
        // Return flattened version if available, otherwise fall back to original
        if ($this->logo_flat) {
            return asset('storage/' . $this->logo_flat);
        }
        return $this->logo_url;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($brand) {
            if (empty($brand->slug)) {
                $brand->slug = Str::slug($brand->name);
            }
        });
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }
}
