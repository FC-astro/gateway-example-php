<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\OperateController;
use App\Http\Controllers\PaylivreCallbackController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;

Route::get('/', [LoginController::class,'index']);
Route::post('/login', [LoginController::class,'login'])->name('login');

Route::post('/logout', [LogoutController::class,'logout'])->name('logout');

Route::get('/register', [RegisterController::class,'index'])->name('register');
Route::post('/register', [RegisterController::class,'store']);
Route::prefix('/transactions')->group(function() {
    Route::get('/', [TransactionController::class,'list'])->name('transactions');
});

Route::post('/operate', [OperateController::class,'index'])->name('operate');

Route::post('/callback',[PaylivreCallbackController::class,'receivePaylivreCallback'])->name('callback');

