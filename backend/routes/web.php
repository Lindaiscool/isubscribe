<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated'], 401);
})->name('login');

Route::get('/invoice/{id}/pdf', [InvoiceController::class, 'showPdf'])->name('invoice.pdf');
