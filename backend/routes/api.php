<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SubscriptionController;

// --------------------
// Public auth routes
// --------------------
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// --------------------
// Protected routes
// --------------------
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // Resources
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('users', UserController::class);
    Route::apiResource('invoices', InvoiceController::class);
    Route::apiResource('subscriptions', SubscriptionController::class);

    // Extra invoice actions
    Route::post('/send-invoices', [InvoiceController::class, 'markAsSent']);
    Route::post('/generate-invoices', [InvoiceController::class, 'generateInvoices']);
    Route::post('/update-invoices', [InvoiceController::class, 'sendInvoices']);
    Route::post('/invoices/mark-all-as-sent', [InvoiceController::class, 'markAllInvoicesAsSent']);

    // Restore customers
    Route::post('/customers/{id}/restore', [CustomerController::class, 'restore']);

    // Current user
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

// --------------------
// Utility routes
// --------------------
Route::get('ping', function () {
    return response()->json(['message' => 'pong'], 200);
});

// Authenticated user (duplicate fallback)
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
