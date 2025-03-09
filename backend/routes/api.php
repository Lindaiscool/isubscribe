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
    // Route::post('/generate-invoices', [InvoiceController::class, 'generateInvoices']);
    Route::post('/send-invoices', [InvoiceController::class, 'markAsSent']);

    //route to generate all invoices
    Route::post('/generate-invoices', [InvoiceController::class, 'generateInvoices']);


    Route::post('/customers/{id}/restore', 'App\Http\Controllers\CustomerController@restore');

    // Additional routes
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/update-invoices', [InvoiceController::class, 'updateInvoices']);

// In routes/web.php of routes/api.php
Route::post('/invoices/mark-all-as-sent', [InvoiceController::class, 'markAllInvoicesAsSent']);

});

// Utility routes
Route::get('ping', function () {
    return response()->json(['message' => 'pong'], 200); // Simple route to check if the API is responsive
});

Route::get('/user', function (Request $request) {
    return $request->user(); // Route to retrieve the authenticated user's information
})->middleware('auth:sanctum'); // Secured with Sanctum middleware to ensure only authenticated users can access this route
