<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check()
    ? redirect(auth()->user()->halamanAwal())
    : redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'peran:super_admin,admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/tagihan', 'admin.tagihan')->name('tagihan');

    Route::middleware('can:super-admin')->group(function () {
        Route::view('/pengaturan', 'admin.pengaturan')->name('pengaturan');
    });
});

Route::middleware(['auth', 'peran:tenant'])->name('penyewa.')->group(function () {
    Route::view('/beranda', 'penyewa.beranda')->name('beranda');
});
