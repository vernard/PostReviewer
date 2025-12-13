<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    return $user->id === $id;
});

// Agency-wide notifications
Broadcast::channel('agency.{agencyId}', function (?User $user, int $agencyId) {
    if (!$user) return false;
    return $user->agency_id === $agencyId;
});

// Brand-specific notifications
Broadcast::channel('brand.{brandId}', function (?User $user, int $brandId) {
    if (!$user) return false;
    return $user->hasBrandAccess(\App\Models\Brand::find($brandId));
});

// Post-specific channel for real-time comments
Broadcast::channel('post.{postId}', function (User $user, int $postId) {
    $post = Post::find($postId);
    return $post && $user->hasBrandAccess($post->brand);
});
