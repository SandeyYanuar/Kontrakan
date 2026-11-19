<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Aksi;
use App\Enums\Peran;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\NomorHpIndonesia;
use App\Support\Aktivitas;
use App\Support\NomorHp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Super admin mengelola akun admin. Akun tidak pernah dihapus, hanya dinonaktifkan.
 */
class AdminController extends Controller
{
    public function index(): View
    {
        $pengelola = User::query()
            ->whereIn('role', [Peran::SuperAdmin, Peran::Admin])
            ->orderByRaw("role = 'super_admin' desc")
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('admin.admin.index', ['pengelola' => $pengelola]);
    }

    public function create(): View
    {
        return view('admin.admin.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'phone' => ['required', new NomorHpIndonesia],
            'email' => ['nullable', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'phone.required' => 'Nomor HP wajib diisi.',
            'email.email' => 'Format email tidak benar.',
            'password.required' => 'Password awal wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        $phone = NomorHp::normalisasi($data['phone']);

        if (User::query()->where('phone', $phone)->exists()) {
            throw ValidationException::withMessages(['phone' => 'Nomor HP ini sudah dipakai akun lain.']);
        }

        $admin = User::create([
            'name' => $data['name'],
            'phone' => $phone,
            'email' => $data['email'] ?? null,
            'password' => $data['password'],
            'role' => Peran::Admin,
            'is_active' => true,
        ]);

        Aktivitas::catat(Aksi::AdminDibuat, $admin, ['nama' => $admin->name]);

        return redirect()->route('admin.admin.index')->with('status', "Admin {$admin->name} ditambahkan.");
    }

    public function nonaktifkan(User $admin): RedirectResponse
    {
        $this->pastikanAdminBiasa($admin);

        $admin->update(['is_active' => false]);
        Aktivitas::catat(Aksi::AdminDinonaktifkan, $admin, ['nama' => $admin->name]);

        return redirect()->route('admin.admin.index')->with('status', "{$admin->name} dinonaktifkan.");
    }

    public function aktifkan(User $admin): RedirectResponse
    {
        $this->pastikanAdminBiasa($admin);

        $admin->update(['is_active' => true]);
        Aktivitas::catat(Aksi::AdminDiaktifkan, $admin, ['nama' => $admin->name]);

        return redirect()->route('admin.admin.index')->with('status', "{$admin->name} diaktifkan kembali.");
    }

    public function editPassword(User $admin): View
    {
        $this->pastikanAdminBiasa($admin);

        return view('admin.admin.reset-password', ['admin' => $admin]);
    }

    public function resetPassword(Request $request, User $admin): RedirectResponse
    {
        $this->pastikanAdminBiasa($admin);

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        $admin->update(['password' => $data['password']]);
        Aktivitas::catat(Aksi::PasswordAdminDireset, $admin);

        return redirect()->route('admin.admin.index')->with('status', "Password {$admin->name} direset. Beri tahu password baru lewat WhatsApp.");
    }

    /**
     * Menu ini hanya mengelola akun berperan admin, bukan super admin atau penyewa.
     */
    private function pastikanAdminBiasa(User $user): void
    {
        abort_unless($user->role === Peran::Admin, 404);
    }
}
