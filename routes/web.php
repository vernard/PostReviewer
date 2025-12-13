<?php

use Illuminate\Support\Facades\Route;

// Named login route for auth redirects
Route::get('/login', function () {
    return view('app');
})->name('login');

// Catch-all route for Vue SPA
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
