<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\KtpPenyewaController;
use App\Http\Controllers\Admin\PembayaranController as AdminPembayaranController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\Admin\PenyewaController;
use App\Http\Controllers\Admin\TagihanController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\AkunController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Penyewa\BerandaController;
use App\Http\Controllers\Penyewa\KtpController;
use App\Http\Controllers\Penyewa\PembayaranController;
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
    Route::get('/tagihan', [TagihanController::class, 'index'])->name('tagihan');
    Route::post('/tagihan/{lease}/ingatkan', [TagihanController::class, 'ingatkan'])->name('tagihan.ingatkan');
    Route::get('/pembayaran/{payment}/bukti', [AdminPembayaranController::class, 'bukti'])->name('pembayaran.bukti');
    Route::post('/pembayaran/{payment}/setujui', [AdminPembayaranController::class, 'setujui'])->name('pembayaran.setujui');
    Route::post('/pembayaran/{payment}/tolak', [AdminPembayaranController::class, 'tolak'])->name('pembayaran.tolak');
    Route::post('/tagihan/{lease}/bulan-berikutnya', [TagihanController::class, 'buatBulanBerikutnya'])->name('tagihan.bulan-berikutnya');
    Route::get('/unit', [UnitController::class, 'index'])->name('unit.index');

    Route::get('/penyewa', [PenyewaController::class, 'index'])->name('penyewa.index');
    Route::get('/penyewa/tambah', [PenyewaController::class, 'create'])->name('penyewa.create');
    Route::post('/penyewa', [PenyewaController::class, 'store'])->name('penyewa.store');
    Route::get('/penyewa/{penyewa}/ubah', [PenyewaController::class, 'edit'])->name('penyewa.edit');
    Route::put('/penyewa/{penyewa}', [PenyewaController::class, 'update'])->name('penyewa.update');
    Route::get('/penyewa/{penyewa}/reset-password', [PenyewaController::class, 'editPassword'])->name('penyewa.reset-password.edit');
    Route::patch('/penyewa/{penyewa}/reset-password', [PenyewaController::class, 'resetPassword'])->name('penyewa.reset-password');
    Route::get('/penyewa/{penyewa}', [KtpPenyewaController::class, 'show'])->name('penyewa.show');
    Route::get('/penyewa/{penyewa}/ktp/foto', [KtpPenyewaController::class, 'foto'])->name('penyewa.ktp.foto');
    Route::post('/penyewa/{penyewa}/ktp/sah', [KtpPenyewaController::class, 'sahkan'])->name('penyewa.ktp.sah');
    Route::post('/penyewa/{penyewa}/ktp/tolak', [KtpPenyewaController::class, 'tolak'])->name('penyewa.ktp.tolak');

    Route::middleware('can:super-admin')->group(function () {
        Route::get('/pengaturan', [PengaturanController::class, 'edit'])->name('pengaturan');
        Route::put('/pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');

        Route::get('/unit/tambah', [UnitController::class, 'create'])->name('unit.create');
        Route::post('/unit', [UnitController::class, 'store'])->name('unit.store');
        Route::get('/unit/{unit}/ubah', [UnitController::class, 'edit'])->name('unit.edit');
        Route::put('/unit/{unit}', [UnitController::class, 'update'])->name('unit.update');

        Route::get('/penyewa/{penyewa}/pindah', [PenyewaController::class, 'formPindah'])->name('penyewa.pindah.form');
        Route::post('/penyewa/{penyewa}/pindah', [PenyewaController::class, 'pindah'])->name('penyewa.pindah');
        Route::get('/penyewa/{penyewa}/akhiri', [PenyewaController::class, 'formAkhiri'])->name('penyewa.akhiri.form');
        Route::post('/penyewa/{penyewa}/akhiri', [PenyewaController::class, 'akhiri'])->name('penyewa.akhiri');
        Route::post('/pembayaran/{payment}/batalkan', [AdminPembayaranController::class, 'batalkan'])->name('pembayaran.batalkan');
        Route::delete('/penyewa/{penyewa}/ktp/foto', [KtpPenyewaController::class, 'hapus'])->name('penyewa.ktp.hapus');

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
    Route::get('/beranda', [BerandaController::class, 'beranda'])->name('beranda');
    Route::get('/riwayat', [BerandaController::class, 'riwayat'])->name('riwayat');
    Route::get('/profil', [BerandaController::class, 'profil'])->name('profil');
    Route::put('/profil/darurat', [BerandaController::class, 'simpanDarurat'])->name('profil.darurat');
    Route::get('/tagihan/{invoice}/bayar', [PembayaranController::class, 'create'])->name('bayar');
    Route::post('/tagihan/{invoice}/bayar', [PembayaranController::class, 'store'])->name('bayar.simpan');
    Route::get('/bukti/{payment}', [PembayaranController::class, 'bukti'])->name('bukti');
    Route::get('/profil/ktp', [KtpController::class, 'edit'])->name('ktp');
    Route::post('/profil/ktp', [KtpController::class, 'simpan'])->name('ktp.simpan');
    Route::get('/profil/ktp/foto', [KtpController::class, 'foto'])->name('ktp.foto');
});
