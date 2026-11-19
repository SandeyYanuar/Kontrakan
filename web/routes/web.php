<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\AkunController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check()
    ? redirect(auth()->user()->halamanAwal())
    : redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::middleware('peran:super_admin,admin,tenant')->prefix('akun')->name('akun.')->group(function () {
        Route::get('/password', [AkunController::class, 'editPassword'])->name('password');
        Route::put('/password', [AkunController::class, 'updatePassword'])->name('password.update');
    });
});

Route::middleware(['auth', 'peran:super_admin,admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/tagihan', 'admin.tagihan')->name('tagihan');
    Route::get('/unit', [UnitController::class, 'index'])->name('unit.index');

    Route::middleware('can:super-admin')->group(function () {
        Route::get('/pengaturan', [PengaturanController::class, 'edit'])->name('pengaturan');
        Route::put('/pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');

        Route::get('/unit/tambah', [UnitController::class, 'create'])->name('unit.create');
        Route::post('/unit', [UnitController::class, 'store'])->name('unit.store');
        Route::get('/unit/{unit}/ubah', [UnitController::class, 'edit'])->name('unit.edit');
        Route::put('/unit/{unit}', [UnitController::class, 'update'])->name('unit.update');

        Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
        Route::get('/admin/tambah', [AdminController::class, 'create'])->name('admin.create');
        Route::post('/admin', [AdminController::class, 'store'])->name('admin.store');
        Route::patch('/admin/{admin}/nonaktifkan', [AdminController::class, 'nonaktifkan'])->name('admin.nonaktifkan');
        Route::patch('/admin/{admin}/aktifkan', [AdminController::class, 'aktifkan'])->name('admin.aktifkan');
        Route::get('/admin/{admin}/reset-password', [AdminController::class, 'editPassword'])->name('admin.reset-password.edit');
        Route::patch('/admin/{admin}/reset-password', [AdminController::class, 'resetPassword'])->name('admin.reset-password');
    });
});

Route::middleware(['auth', 'peran:tenant'])->name('penyewa.')->group(function () {
    Route::view('/beranda', 'penyewa.beranda')->name('beranda');
});
