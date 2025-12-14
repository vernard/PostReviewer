<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'agency_id',
        'name',
        'email',
        'password',
        'role',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
        ];
    }

    /**
     * The user's currently selected agency.
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /**
     * All agencies the user belongs to.
     */
    public function agencies(): BelongsToMany
    {
        return $this->belongsToMany(Agency::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get the user's role within a specific agency.
     */
    public function roleInAgency(?Agency $agency): ?string
    {
        if (!$agency) {
            return null;
        }

        $pivot = $this->agencies()->where('agency_id', $agency->id)->first();
        return $pivot?->pivot?->role;
    }

    /**
     * Check if user is admin/manager in their current agency.
     */
    public function isManagerInCurrentAgency(): bool
    {
        $role = $this->roleInAgency($this->agency);
        return in_array($role, ['admin', 'manager']);
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class)->withTimestamps();
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'created_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['admin', 'manager']);
    }

    public function canReview(): bool
    {
        return in_array($this->role, ['admin', 'manager', 'reviewer']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin === true;
    }

    public function hasBrandAccess(Brand $brand): bool
    {
        // Super admins have access to everything
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check if user belongs to the brand's agency
        $agencyMembership = $this->agencies()->where('agency_id', $brand->agency_id)->first();

        if (!$agencyMembership) {
            return false;
        }

        // Admins and managers in that agency have access to all its brands
        $roleInAgency = $agencyMembership->pivot->role;
        if (in_array($roleInAgency, ['admin', 'manager'])) {
            return true;
        }

        // Others need explicit brand assignment
        return $this->brands()->where('brands.id', $brand->id)->exists();
    }

    /**
     * Check if user belongs to a specific agency.
     */
    public function belongsToAgency(Agency $agency): bool
    {
        return $this->agencies()->where('agency_id', $agency->id)->exists();
    }
}
