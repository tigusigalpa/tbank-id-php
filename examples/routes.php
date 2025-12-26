<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TBankAuthController;

Route::get('/auth/tbank', [TBankAuthController::class, 'redirect'])->name('tbank.login');
Route::get('/auth/tbank/callback', [TBankAuthController::class, 'callback'])->name('tbank.callback');
Route::post('/auth/tbank/logout', [TBankAuthController::class, 'logout'])->name('tbank.logout');

Route::middleware(['tbank.auth'])->group(function () {
    Route::get('/dashboard', function () {
        $user = session('tbank_user');
        return view('dashboard', compact('user'));
    });
});
