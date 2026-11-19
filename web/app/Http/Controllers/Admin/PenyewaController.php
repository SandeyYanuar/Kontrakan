<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Aksi;
use App\Enums\Peran;
use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\Unit;
use App\Models\User;
use App\Rules\NomorHpIndonesia;
use App\Support\Aktivitas;
use App\Support\KodePenyewa;
use App\Support\NomorHp;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Penyewa dan kontrak sewanya.
 * Admin: tambah, ubah data, reset password. Super admin: juga pindah unit dan akhiri sewa.
 */
class PenyewaController extends Controller
{
    public function index(): View
    {
        $penyewa = User::query()
            ->where('role', Peran::Penyewa)
            ->with('leaseAktif.unit', 'identitas')
            ->orderByDesc('is_active')
            ->orderBy('kode_penyewa')
            ->get();

        return view('admin.penyewa.index', ['penyewa' => $penyewa]);
    }

    public function create(): View
    {
        return view('admin.penyewa.form', [
            'penyewa' => new User,
            'unitKosong' => Unit::query()->kosong()->orderBy('kode_unit')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'phone' => ['required', new NomorHpIndonesia],
            'email' => ['nullable', 'email', 'max:255'],
            'unit_id' => ['required', 'exists:units,id'],
            'tgl_mulai' => ['required', 'date'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], $this->pesan());

        $phone = $this->nomorUnik($data['phone']);
        $unit = $this->unitKosong((int) $data['unit_id']);

        $penyewa = DB::transaction(function () use ($data, $phone, $unit): User {
            $penyewa = User::create([
                'name' => $data['name'],
                'phone' => $phone,
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
                'role' => Peran::Penyewa,
                'is_active' => true,
                'kode_penyewa' => KodePenyewa::berikutnya(),
            ]);

            $penyewa->leases()->create([
                'unit_id' => $unit->id,
                'tgl_mulai' => $data['tgl_mulai'],
                'status' => Lease::AKTIF,
            ]);

            Aktivitas::catat(Aksi::PenyewaDitambah, $penyewa, [
                'kode_penyewa' => $penyewa->kode_penyewa,
                'nama' => $penyewa->name,
                'unit' => $unit->kode_unit,
            ]);

            return $penyewa;
        });

        return redirect()->route('admin.penyewa.index')
            ->with('status', "{$penyewa->name} ({$penyewa->kode_penyewa}) ditambahkan di unit {$unit->kode_unit}.");
    }

    public function edit(User $penyewa): View
    {
        $this->pastikanPenyewa($penyewa);

        return view('admin.penyewa.form', ['penyewa' => $penyewa, 'unitKosong' => collect()]);
    }

    public function update(Request $request, User $penyewa): RedirectResponse
    {
        $this->pastikanPenyewa($penyewa);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'phone' => ['required', new NomorHpIndonesia],
            'email' => ['nullable', 'email', 'max:255'],
            'phone_darurat' => ['nullable', new NomorHpIndonesia],
        ], $this->pesan());

        $penyewa->fill([
            'name' => $data['name'],
            'phone' => $this->nomorUnik($data['phone'], $penyewa),
            'email' => $data['email'] ?? null,
            'phone_darurat' => ($data['phone_darurat'] ?? null) ? NomorHp::normalisasi($data['phone_darurat']) : null,
        ]);

        if ($penyewa->isDirty()) {
            $perubahan = array_keys($penyewa->getDirty());
            $penyewa->save();
            Aktivitas::catat(Aksi::PenyewaDiubah, $penyewa, ['kode_penyewa' => $penyewa->kode_penyewa, 'perubahan' => $perubahan]);
        }

        return redirect()->route('admin.penyewa.index')->with('status', "Data {$penyewa->name} disimpan.");
    }

    public function editPassword(User $penyewa): View
    {
        $this->pastikanPenyewa($penyewa);

        return view('admin.penyewa.reset-password', ['penyewa' => $penyewa]);
    }

    public function resetPassword(Request $request, User $penyewa): RedirectResponse
    {
        $this->pastikanPenyewa($penyewa);

        $data = $request->validate(['password' => ['required', 'confirmed', Password::min(8)]], $this->pesan());

        $penyewa->update(['password' => $data['password']]);
        Aktivitas::catat(Aksi::PasswordPenyewaDireset, $penyewa, ['kode_penyewa' => $penyewa->kode_penyewa]);

        return redirect()->route('admin.penyewa.index')
            ->with('status', "Password {$penyewa->name} direset. Beri tahu password baru lewat WhatsApp.");
    }

    public function formPindah(User $penyewa): View
    {
        $this->pastikanPenyewa($penyewa);
        abort_unless($penyewa->leaseAktif, 404);

        return view('admin.penyewa.pindah', [
            'penyewa' => $penyewa->load('leaseAktif.unit'),
            'unitKosong' => Unit::query()->kosong()->orderBy('kode_unit')->get(),
        ]);
    }

    public function pindah(Request $request, User $penyewa): RedirectResponse
    {
        $this->pastikanPenyewa($penyewa);
        $lama = $penyewa->leaseAktif;
        abort_unless($lama, 404);

        $data = $request->validate([
            'unit_id' => ['required', 'exists:units,id'],
            'tgl_pindah' => ['required', 'date', 'after:'.$lama->tgl_mulai->toDateString()],
        ], $this->pesan());

        $tujuan = $this->unitKosong((int) $data['unit_id']);
        $tglPindah = CarbonImmutable::parse($data['tgl_pindah']);
        $asal = $lama->unit;

        DB::transaction(function () use ($penyewa, $lama, $tujuan, $tglPindah, $asal): void {
            $lama->update(['status' => Lease::SELESAI, 'tgl_selesai' => $tglPindah->subDay()]);
            $penyewa->leases()->create(['unit_id' => $tujuan->id, 'tgl_mulai' => $tglPindah, 'status' => Lease::AKTIF]);

            Aktivitas::catat(Aksi::PenyewaPindahUnit, $penyewa, [
                'kode_penyewa' => $penyewa->kode_penyewa,
                'dari' => $asal->kode_unit,
                'ke' => $tujuan->kode_unit,
            ]);
        });

        return redirect()->route('admin.penyewa.index')
            ->with('status', "{$penyewa->name} dipindahkan dari {$asal->kode_unit} ke {$tujuan->kode_unit}.");
    }

    public function formAkhiri(User $penyewa): View
    {
        $this->pastikanPenyewa($penyewa);
        abort_unless($penyewa->leaseAktif, 404);

        return view('admin.penyewa.akhiri', ['penyewa' => $penyewa->load('leaseAktif.unit')]);
    }

    public function akhiri(Request $request, User $penyewa): RedirectResponse
    {
        $this->pastikanPenyewa($penyewa);
        $lease = $penyewa->leaseAktif;
        abort_unless($lease, 404);

        $data = $request->validate([
            'tgl_selesai' => ['required', 'date', 'after_or_equal:'.$lease->tgl_mulai->toDateString()],
        ], $this->pesan());

        DB::transaction(function () use ($penyewa, $lease, $data): void {
            $lease->update(['status' => Lease::SELESAI, 'tgl_selesai' => $data['tgl_selesai']]);
            $penyewa->update(['is_active' => false]);

            Aktivitas::catat(Aksi::SewaDiakhiri, $penyewa, [
                'kode_penyewa' => $penyewa->kode_penyewa,
                'unit' => $lease->unit->kode_unit,
                'tgl_selesai' => $data['tgl_selesai'],
            ]);
        });

        return redirect()->route('admin.penyewa.index')
            ->with('status', "Sewa {$penyewa->name} di unit {$lease->unit->kode_unit} diakhiri. Akunnya sudah dinonaktifkan.");
    }

    /**
     * Menu ini hanya untuk akun penyewa, bukan admin.
     */
    private function pastikanPenyewa(User $user): void
    {
        abort_unless($user->isPenyewa(), 404);
    }

    private function nomorUnik(string $ketikan, ?User $kecuali = null): string
    {
        $phone = NomorHp::normalisasi($ketikan);

        $dipakai = User::query()
            ->where('phone', $phone)
            ->when($kecuali, fn ($query) => $query->whereKeyNot($kecuali->getKey()))
            ->exists();

        if ($dipakai) {
            throw ValidationException::withMessages(['phone' => 'Nomor HP ini sudah dipakai akun lain.']);
        }

        return $phone;
    }

    private function unitKosong(int $unitId): Unit
    {
        $unit = Unit::query()->kosong()->find($unitId);

        if (! $unit) {
            throw ValidationException::withMessages(['unit_id' => 'Unit ini sudah terisi. Pilih unit yang kosong.']);
        }

        return $unit;
    }

    /**
     * @return array<string, string>
     */
    private function pesan(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'phone.required' => 'Nomor HP wajib diisi.',
            'email.email' => 'Format email tidak benar.',
            'unit_id.required' => 'Pilih unit.',
            'tgl_mulai.required' => 'Tanggal mulai sewa wajib diisi.',
            'tgl_pindah.required' => 'Tanggal pindah wajib diisi.',
            'tgl_pindah.after' => 'Tanggal pindah harus setelah tanggal mulai sewa sekarang.',
            'tgl_selesai.required' => 'Tanggal selesai wajib diisi.',
            'tgl_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai sewa.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ];
    }
}
