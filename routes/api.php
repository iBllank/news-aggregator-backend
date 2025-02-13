<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PreferenceController;

// Public - Api
Route::get('/articles', [ArticleController::class, 'index']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/filters', [ArticleController::class, 'filters']);

// Requires auth sanctum token - Api
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/preferences', [PreferenceController::class, 'store']);
    Route::get('/preferences', [PreferenceController::class, 'show']);
});
