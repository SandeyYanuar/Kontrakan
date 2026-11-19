<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Pengaturan;
use App\Models\User;
use App\Support\NomorHp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Halaman login dengan nomor HP WhatsApp.
     */
    public function create(): View
    {
        $nomorAdmin = Pengaturan::nomorWhatsAppAdmin();

        $linkLupaPassword = $nomorAdmin
            ? NomorHp::linkWhatsApp($nomorAdmin, 'Halo, saya lupa password akun kontrakan. Nama saya: ')
            : null;

        return view('auth.login', ['linkLupaPassword' => $linkLupaPassword]);
    }

    /**
     * Proses login.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'phone.required' => 'Nomor HP wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $phone = NomorHp::normalisasi($request->string('phone'));

        if ($phone === null) {
            throw ValidationException::withMessages(['phone' => 'Nomor HP harus diawali 08 atau 62.']);
        }

        $kunci = 'login:'.$phone.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($kunci, config('kontrakan.batas_percobaan_login'))) {
            $detik = RateLimiter::availableIn($kunci);

            throw ValidationException::withMessages([
                'phone' => "Terlalu banyak percobaan. Coba lagi dalam {$detik} detik.",
            ]);
        }

        $user = User::query()->where('phone', $phone)->first();

        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            RateLimiter::hit($kunci);

            throw ValidationException::withMessages(['phone' => 'Nomor HP atau password salah.']);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'phone' => 'Akun ini sudah dinonaktifkan. Hubungi pengelola kontrakan.',
            ]);
        }

        RateLimiter::clear($kunci);
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended($user->halamanAwal());
    }

    /**
     * Logout dan kembali ke halaman login.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
