<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Aksi;
use App\Http\Controllers\Controller;
use App\Models\Pengaturan;
use App\Rules\NomorHpIndonesia;
use App\Support\Aktivitas;
use App\Support\NomorHp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PengaturanController extends Controller
{
    public function edit(): View
    {
        return view('admin.pengaturan', ['pengaturan' => Pengaturan::semua()]);
    }

    /**
     * Foto latar dashboard admin: unggahan super admin, atau foto rumah bawaan.
     */
    public function latar(): StreamedResponse|RedirectResponse
    {
        $path = Pengaturan::ambil('latar_dashboard');

        if (! $path || ! Storage::disk('local')->exists($path)) {
            return redirect(asset('img/latar-rumah.jpg'));
        }

        return Storage::disk('local')->response($path, headers: ['Cache-Control' => 'private, max-age=86400']);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_kontrakan' => ['required', 'string', 'max:60'],
            'bank_nama' => ['nullable', 'string', 'max:40'],
            'bank_nomor' => ['nullable', 'string', 'max:30', 'regex:/^[\d\s\-]+$/'],
            'bank_atas_nama' => ['nullable', 'string', 'max:60'],
            'whatsapp_admin' => ['nullable', new NomorHpIndonesia],
            'tanggal_mulai_bayar' => ['required', 'integer', 'between:1,28'],
            'tanggal_jatuh_tempo' => ['required', 'integer', 'between:1,28', 'different:tanggal_mulai_bayar'],
            'latar' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'latar_bawaan' => ['nullable', 'boolean'],
        ], [
            'latar.image' => 'Foto latar harus berupa gambar JPG atau PNG.',
            'latar.mimes' => 'Foto latar harus berupa gambar JPG atau PNG.',
            'latar.max' => 'Ukuran foto latar maksimal 2 MB.',
            'nama_kontrakan.required' => 'Nama kontrakan wajib diisi.',
            'bank_nomor.regex' => 'Nomor rekening hanya boleh berisi angka, spasi, atau strip.',
            'tanggal_mulai_bayar.between' => 'Tanggal harus antara 1 dan 28.',
            'tanggal_jatuh_tempo.between' => 'Tanggal harus antara 1 dan 28.',
            'tanggal_jatuh_tempo.different' => 'Jatuh tempo harus berbeda dari tanggal mulai bayar.',
        ]);

        $data += ['bank_nama' => null, 'bank_nomor' => null, 'bank_atas_nama' => null, 'whatsapp_admin' => null];
        $data['whatsapp_admin'] = $data['whatsapp_admin'] ? NomorHp::normalisasi($data['whatsapp_admin']) : null;
        $data['tanggal_mulai_bayar'] = (string) (int) $data['tanggal_mulai_bayar'];
        $data['tanggal_jatuh_tempo'] = (string) (int) $data['tanggal_jatuh_tempo'];

        $lama = Pengaturan::semua();

        unset($data['latar'], $data['latar_bawaan']);
        $data['latar_dashboard'] = $lama['latar_dashboard'];

        if ($request->hasFile('latar')) {
            $data['latar_dashboard'] = $request->file('latar')->store('latar', 'local');
        } elseif ($request->boolean('latar_bawaan')) {
            $data['latar_dashboard'] = null;
        }

        if ($lama['latar_dashboard'] && $lama['latar_dashboard'] !== $data['latar_dashboard']) {
            Storage::disk('local')->delete($lama['latar_dashboard']);
        }
        $perubahan = [];

        foreach ($data as $kunci => $baru) {
            if ($lama[$kunci] !== $baru) {
                $perubahan[$kunci] = ['lama' => $lama[$kunci], 'baru' => $baru];
            }
        }

        if ($perubahan !== []) {
            Pengaturan::simpan($data);
            Aktivitas::catat(Aksi::PengaturanDiubah, data: $perubahan);
        }

        return redirect()->route('admin.pengaturan')->with('status', 'Pengaturan disimpan.');
    }
}
