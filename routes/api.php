<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CryptoAccountController;
use App\Http\Controllers\CryptoPriceController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Interactive docs: https://chandrasura25.github.io/Crypto-Wallet-API/
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('crypto-accounts', CryptoAccountController::class);

    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
    Route::post('/transfer', [TransactionController::class, 'transferFunds']);
    Route::post('/transferFunds', [TransactionController::class, 'transferFunds']); // kept for backwards compatibility

    Route::post('/convert', [CryptoPriceController::class, 'convertBalance']);
});
