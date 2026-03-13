<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\BoardMemberController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\ColumnController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'throttle:api'])->group(function () {
    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    Route::prefix('boards/{board}')->group(function () {
        // Activity routes
        Route::get('/activities', [ActivityController::class, 'index']);

        // Search & Filter routes
        Route::middleware('throttle:search')->group(function () {
            Route::get('/search', [SearchController::class, 'search']);
            Route::get('/filter', [SearchController::class, 'filter']);
        });

        // Member routes
        Route::get('/members', [BoardMemberController::class, 'index']);
        Route::get('/members/search-users', [BoardMemberController::class, 'searchUsers']);
        Route::post('/members', [BoardMemberController::class, 'store']);
        Route::put('/members/{member}', [BoardMemberController::class, 'update']);
        Route::delete('/members/{member}', [BoardMemberController::class, 'destroy']);

        // Column routes
        Route::post('/columns', [ColumnController::class, 'store']);
        Route::put('/columns/{column}', [ColumnController::class, 'update']);
        Route::delete('/columns/{column}', [ColumnController::class, 'destroy']);
        Route::post('/columns/{column}/reorder', [ColumnController::class, 'reorder']);

        // Card routes
        Route::post('/columns/{column}/cards', [CardController::class, 'store']);
        Route::put('/cards/{card}', [CardController::class, 'update']);
        Route::delete('/cards/{card}', [CardController::class, 'destroy']);
        Route::post('/cards/{card}/move', [CardController::class, 'move']);

        // Comment routes
        Route::get('/cards/{card}/comments', [CommentController::class, 'index']);
        Route::post('/cards/{card}/comments', [CommentController::class, 'store']);
        Route::delete('/cards/{card}/comments/{comment}', [CommentController::class, 'destroy']);

        // Export routes
        Route::get('/export/json', [ExportController::class, 'json']);
        Route::get('/export/markdown', [ExportController::class, 'markdown']);
    });
});
