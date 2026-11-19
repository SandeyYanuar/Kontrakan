<?php

namespace App\Http\Controllers\Penyewa;

use App\Enums\Aksi;
use App\Http\Controllers\Controller;
use App\Models\TenantIdentity;
use App\Support\Aktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Penyewa mengupload KTP dari Profil.
 */
class KtpController extends Controller
{
    public function edit(Request $request): View
    {
        return view('penyewa.ktp', ['identitas' => $request->user()->identitas]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $penyewa = $request->user();
        $identitas = $penyewa->identitas;

        abort_if($identitas && ! $identitas->bisaDiupload(), 403, 'KTP sudah dinyatakan sah. Hubungi pengelola untuk menggantinya.');

        $request->merge(['nik' => preg_replace('/\s+/', '', (string) $request->input('nik'))]);

        $data = $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'nik' => ['required', 'digits:16'],
            'nama_ktp' => ['required', 'string', 'max:100'],
        ], [
            'foto.required' => 'Pilih foto KTP.',
            'foto.image' => 'Foto KTP harus berupa gambar JPG atau PNG.',
            'foto.mimes' => 'Foto KTP harus berupa gambar JPG atau PNG.',
            'foto.max' => 'Ukuran foto maksimal 2 MB.',
            'nik.required' => 'NIK wajib diisi.',
            'nik.digits' => 'NIK harus 16 angka, sama seperti di KTP.',
            'nama_ktp.required' => 'Nama sesuai KTP wajib diisi.',
        ]);

        $fotoLama = $identitas?->foto_path;
        $path = $request->file('foto')->store('ktp/'.$penyewa->id, 'local');

        $identitas = TenantIdentity::updateOrCreate(['user_id' => $penyewa->id], [
            'nik' => $data['nik'],
            'nama_ktp' => $data['nama_ktp'],
            'foto_path' => $path,
            'status' => TenantIdentity::MENUNGGU,
            'alasan' => null,
            'verified_by' => null,
            'verified_at' => null,
        ]);

        $penyewa->setRelation('identitas', $identitas);

        if ($fotoLama) {
            Storage::disk('local')->delete($fotoLama);
        }

        Aktivitas::catat(Aksi::KtpDiupload, $penyewa, ['kode_penyewa' => $penyewa->kode_penyewa]);

        return redirect()->route('penyewa.ktp')->with('status', 'KTP terkirim. Pengelola akan mengeceknya.');
    }

    public function foto(Request $request): StreamedResponse
    {
        $path = $request->user()->identitas()->value('foto_path');
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, headers: ['Cache-Control' => 'private, no-store']);
    }
}
