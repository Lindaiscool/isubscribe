<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SubscriptionController;

// Authentication routes
Route::post(uri: '/register', action: [AuthController::class, 'register']); // Route to handle user registration
Route::post(uri: '/login', action: [AuthController::class, 'login']); // Route to handle user login
Route::post(uri: '/logout', action: [AuthController::class, 'logout'])
    ->middleware(middleware: 'auth:sanctum'); // Route to handle user logout, secured with Sanctum middleware

// Resource routes for handling CRUD operations via API Resources
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('customers', CustomerController::class); // Routes for customer CRUD operations
    Route::apiResource('users', UserController::class); // Routes for user CRUD operations
    Route::apiResource('invoices', InvoiceController::class); // Routes for invoice CRUD operations
    Route::apiResource('subscriptions', SubscriptionController::class); // Routes for subscription CRUD operations
});

// Utility routes
Route::get('ping', function () {
    return response()->json(['message' => 'pong'], 200); // Simple route to check if the API is responsive
});

Route::get('/user', function (Request $request) {
    return $request->user(); // Route to retrieve the authenticated user's information
})->middleware('auth:sanctum'); // Secured with Sanctum middleware to ensure only authenticated users can access this route
