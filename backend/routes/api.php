<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SubscriptionController;

// Authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Resource routes for handling CRUD operations via API Resources
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']); // Verplaats deze naar binnen de middleware-groep

    // API resource routes
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('users', UserController::class);
    Route::apiResource('invoices', InvoiceController::class);
    Route::apiResource('subscriptions', SubscriptionController::class);

    // Additional routes
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

// Utility routes
Route::get('ping', function () {
    return response()->json(['message' => 'pong'], 200); // Simple route to check if the API is responsive
});

Route::get('/user', function (Request $request) {
    return $request->user(); // Route to retrieve the authenticated user's information
})->middleware('auth:sanctum'); // Secured with Sanctum middleware to ensure only authenticated users can access this route
