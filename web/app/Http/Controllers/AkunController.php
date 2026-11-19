<?php

namespace App\Http\Controllers;

use App\Enums\Aksi;
use App\Support\Aktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Pengaturan akun milik pengguna yang sedang login.
 */
class AkunController extends Controller
{
    public function editPassword(): View
    {
        return view('akun.password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password_lama' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'password_lama.required' => 'Password lama wajib diisi.',
            'password_lama.current_password' => 'Password lama salah.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        $request->user()->update(['password' => $data['password']]);
        Aktivitas::catat(Aksi::PasswordDiganti, $request->user());

        return redirect()->route('akun.password')->with('status', 'Password diganti.');
    }
}
