<?php

use App\Http\Controllers\Api\AgencyController;
use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CollectionController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\HomepageController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\PublicApprovalController;
use App\Http\Controllers\PublicReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes (with rate limiting to prevent brute force attacks)
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/invitation/{token}/accept', [AuthController::class, 'acceptInvitation'])->middleware('throttle:invitation');

// Homepage tracking (public, rate limited)
Route::post('/homepage/track', [HomepageController::class, 'trackUsage'])->middleware('throttle:60,1');

// Public approval routes (rate limited to prevent token enumeration)
Route::middleware('throttle:public-approval')->group(function () {
    // Collection-based approval (multiple posts)
    Route::get('/public/approval/{token}', [PublicApprovalController::class, 'show']);
    Route::post('/public/approval/{token}/submit', [PublicApprovalController::class, 'submit']);

    // Single post review via email invite
    Route::get('/public/review/{token}', [PublicReviewController::class, 'show']);
    Route::post('/public/review/{token}/approve', [PublicReviewController::class, 'approve']);
    Route::post('/public/review/{token}/request-changes', [PublicReviewController::class, 'requestChanges']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Agency
    Route::get('/agency', [AgencyController::class, 'show']);
    Route::put('/agency', [AgencyController::class, 'update']);
    Route::get('/agency/storage', [AgencyController::class, 'storage']);

    // Users / Team
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users/invite', [UserController::class, 'invite']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);

    // Brands
    Route::apiResource('brands', BrandController::class);
    Route::post('/brands/{brand}/users', [BrandController::class, 'addUser']);
    Route::delete('/brands/{brand}/users/{user}', [BrandController::class, 'removeUser']);
    Route::get('/brands/{brand}/default-reviewers', [BrandController::class, 'getDefaultReviewers']);

    // Media
    Route::get('/media', [MediaController::class, 'index']);
    Route::post('/media', [MediaController::class, 'store']);
    Route::get('/media/{media}', [MediaController::class, 'show']);
    Route::delete('/media/{media}', [MediaController::class, 'destroy']);

    // Posts
    Route::apiResource('posts', PostController::class);
    Route::post('/posts/{post}/submit', [PostController::class, 'submitForApproval']);
    Route::post('/posts/{post}/invite-reviewers', [PostController::class, 'inviteReviewers']);
    Route::post('/posts/{post}/duplicate', [PostController::class, 'duplicate']);

    // Post Media
    Route::post('/posts/{post}/media', [PostController::class, 'attachMedia']);
    Route::delete('/posts/{post}/media/{media}', [PostController::class, 'detachMedia']);
    Route::put('/posts/{post}/media/reorder', [PostController::class, 'reorderMedia']);

    // Collections
    Route::apiResource('collections', CollectionController::class);
    Route::post('/collections/{collection}/generate-link', [CollectionController::class, 'generateApprovalLink']);
    Route::post('/collections/{collection}/submit', [CollectionController::class, 'submitForApproval']);
    Route::post('/collections/{collection}/posts', [CollectionController::class, 'addPosts']);
    Route::delete('/collections/{collection}/posts', [CollectionController::class, 'removePosts']);

    // Approvals
    Route::get('/approvals', [ApprovalController::class, 'index']);
    Route::post('/approvals/{approvalRequest}/approve', [ApprovalController::class, 'approve']);
    Route::post('/approvals/{approvalRequest}/request-changes', [ApprovalController::class, 'requestChanges']);

    // Comments
    Route::get('/posts/{post}/comments', [CommentController::class, 'index']);
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
    Route::put('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
    Route::post('/comments/{comment}/resolve', [CommentController::class, 'resolve']);

    // Dashboard stats
    Route::get('/dashboard/stats', [AgencyController::class, 'dashboardStats']);
});

// Super admin routes
Route::middleware(['auth:sanctum', 'super-admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/users', [AdminController::class, 'users']);
    Route::get('/agencies', [AdminController::class, 'agencies']);
    Route::put('/agencies/{agency}/quota', [AdminController::class, 'updateAgencyQuota']);
    Route::post('/impersonate/{user}', [AdminController::class, 'impersonate']);
});
