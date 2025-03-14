<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Mail;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated'], 401);
})->name('login');

Route::get('/invoice/{id}/pdf', [InvoiceController::class, 'showPdf'])->name('invoice.pdf');



Route::get('/test', function () {
    Mail::raw('This is a test email from Laravel!', function ($message) {
        $message->to('deboer.linda@icloud.com')->subject('Test Email');
    });

    return 'Test email sent!';
});

