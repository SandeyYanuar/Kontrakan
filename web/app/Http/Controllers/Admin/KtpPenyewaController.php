<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Aksi;
use App\Http\Controllers\Controller;
use App\Models\TenantIdentity;
use App\Models\User;
use App\Support\Aktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Detail penyewa dan pemeriksaan KTP oleh admin. Setiap kali foto KTP dibuka, tercatat di riwayat.
 */
class KtpPenyewaController extends Controller
{
    public function show(User $penyewa): View
    {
        abort_unless($penyewa->isPenyewa(), 404);

        return view('admin.penyewa.show', [
            'penyewa' => $penyewa->load('identitas.pemeriksa', 'leaseAktif.unit', 'leases.unit'),
        ]);
    }

    public function foto(User $penyewa): StreamedResponse
    {
        abort_unless($penyewa->isPenyewa(), 404);
        $path = $penyewa->identitas?->foto_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        Aktivitas::catat(Aksi::KtpDilihat, $penyewa, ['kode_penyewa' => $penyewa->kode_penyewa]);

        return Storage::disk('local')->response($path, headers: ['Cache-Control' => 'private, no-store']);
    }

    public function sahkan(Request $request, User $penyewa): RedirectResponse
    {
        $identitas = $this->identitasUntukDiperiksa($penyewa);

        $identitas->update([
            'status' => TenantIdentity::SAH,
            'alasan' => null,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        Aktivitas::catat(Aksi::KtpDisahkan, $penyewa, ['kode_penyewa' => $penyewa->kode_penyewa]);

        return redirect()->route('admin.penyewa.show', $penyewa)->with('status', "KTP {$penyewa->name} ditandai sah.");
    }

    public function tolak(Request $request, User $penyewa): RedirectResponse
    {
        $identitas = $this->identitasUntukDiperiksa($penyewa);

        $data = $request->validate(
            ['alasan' => ['required', 'string', 'max:255']],
            ['alasan.required' => 'Tulis alasannya supaya penyewa tahu apa yang perlu diperbaiki.'],
        );

        $identitas->update([
            'status' => TenantIdentity::DITOLAK,
            'alasan' => $data['alasan'],
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        Aktivitas::catat(Aksi::KtpDitolak, $penyewa, ['kode_penyewa' => $penyewa->kode_penyewa, 'alasan' => $data['alasan']]);

        return redirect()->route('admin.penyewa.show', $penyewa)->with('status', "{$penyewa->name} diminta upload ulang KTP.");
    }

    /**
     * Super admin menghapus foto dan NIK penyewa yang sudah keluar. Nama sesuai KTP tetap disimpan.
     */
    public function hapus(User $penyewa): RedirectResponse
    {
        abort_unless($penyewa->isPenyewa() && $penyewa->identitas, 404);
        abort_if($penyewa->is_active, 422, 'Foto KTP hanya bisa dihapus setelah sewa diakhiri.');

        $identitas = $penyewa->identitas;

        if ($identitas->foto_path) {
            Storage::disk('local')->delete($identitas->foto_path);
        }

        $identitas->update([
            'foto_path' => null,
            'nik' => null,
            'status' => TenantIdentity::BELUM_ADA,
        ]);

        Aktivitas::catat(Aksi::KtpDihapus, $penyewa, ['kode_penyewa' => $penyewa->kode_penyewa]);

        return redirect()->route('admin.penyewa.show', $penyewa)->with('status', "Foto dan NIK {$penyewa->name} sudah dihapus.");
    }

    private function identitasUntukDiperiksa(User $penyewa): TenantIdentity
    {
        abort_unless($penyewa->isPenyewa() && $penyewa->identitas && $penyewa->statusKtp() !== TenantIdentity::BELUM_ADA, 404);

        return $penyewa->identitas;
    }
}
