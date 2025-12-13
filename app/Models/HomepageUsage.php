<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomepageUsage extends Model
{
    protected $fillable = [
        'ip_address',
        'session_id',
        'user_id',
        'action',
        'platform',
        'media_type',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the count of usages for an IP in the last hour.
     */
    public static function recentCountForIp(string $ip, int $minutes = 60): int
    {
        return static::where('ip_address', $ip)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * Check if the IP should see a signup prompt.
     * Returns true after 3 uses in the last hour.
     */
    public static function shouldShowSignupPrompt(string $ip): bool
    {
        return static::recentCountForIp($ip) >= 3;
    }
}
