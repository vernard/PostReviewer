<?php

use App\Http\Controllers\Api\AgencyController;
use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/invitation/{token}/accept', [AuthController::class, 'acceptInvitation']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Agency
    Route::get('/agency', [AgencyController::class, 'show']);
    Route::put('/agency', [AgencyController::class, 'update']);

    // Users / Team
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users/invite', [UserController::class, 'invite']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);

    // Brands
    Route::apiResource('brands', BrandController::class);
    Route::post('/brands/{brand}/users', [BrandController::class, 'addUser']);
    Route::delete('/brands/{brand}/users/{user}', [BrandController::class, 'removeUser']);

    // Media
    Route::get('/media', [MediaController::class, 'index']);
    Route::post('/media', [MediaController::class, 'store']);
    Route::get('/media/{media}', [MediaController::class, 'show']);
    Route::delete('/media/{media}', [MediaController::class, 'destroy']);

    // Posts
    Route::apiResource('posts', PostController::class);
    Route::post('/posts/{post}/submit', [PostController::class, 'submitForApproval']);
    Route::post('/posts/{post}/duplicate', [PostController::class, 'duplicate']);

    // Post Media
    Route::post('/posts/{post}/media', [PostController::class, 'attachMedia']);
    Route::delete('/posts/{post}/media/{media}', [PostController::class, 'detachMedia']);
    Route::put('/posts/{post}/media/reorder', [PostController::class, 'reorderMedia']);

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
