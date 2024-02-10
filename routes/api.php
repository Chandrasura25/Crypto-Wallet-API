<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CryptoAccountController;
use App\Http\Controllers\CryptoPriceController;
use App\Http\Controllers\TransactionController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Private Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::resource('crypto-accounts', CryptoAccountController::class);
    
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transferFunds', [TransactionController::class, 'transferFunds']);
    Route::get('/transactions/{transaction}',[TransactionController::class, 'show']);
    
    Route::post('/convert', [CryptoPriceController::class, 'convertBalance']);
});