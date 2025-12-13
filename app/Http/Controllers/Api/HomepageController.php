<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomepageUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomepageController extends Controller
{
    /**
     * Track homepage mockup usage.
     */
    public function trackUsage(Request $request): JsonResponse
    {
        $request->validate([
            'action' => ['required', 'string', 'in:file_upload,export,platform_change'],
            'platform' => ['nullable', 'string', 'max:50'],
            'media_type' => ['nullable', 'string', 'in:image,video'],
        ]);

        $ip = $request->ip();

        HomepageUsage::create([
            'ip_address' => $ip,
            'session_id' => $request->session()->getId(),
            'user_id' => $request->user()?->id,
            'action' => $request->action,
            'platform' => $request->platform,
            'media_type' => $request->media_type,
            'user_agent' => $request->userAgent(),
        ]);

        // Check if we should show signup prompt (after 3 uses)
        $usageCount = HomepageUsage::recentCountForIp($ip);
        $showSignupPrompt = $usageCount >= 3 && !$request->user();

        return response()->json([
            'tracked' => true,
            'usage_count' => $usageCount,
            'show_signup_prompt' => $showSignupPrompt,
        ]);
    }
}
