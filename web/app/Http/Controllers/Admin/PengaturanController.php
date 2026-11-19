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
use Illuminate\View\View;

class PengaturanController extends Controller
{
    public function edit(): View
    {
        return view('admin.pengaturan', ['pengaturan' => Pengaturan::semua()]);
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
            'tanggal_jatuh_tempo' => ['required', 'integer', 'between:1,28', 'gte:tanggal_mulai_bayar'],
        ], [
            'nama_kontrakan.required' => 'Nama kontrakan wajib diisi.',
            'bank_nomor.regex' => 'Nomor rekening hanya boleh berisi angka, spasi, atau strip.',
            'tanggal_mulai_bayar.between' => 'Tanggal harus antara 1 dan 28.',
            'tanggal_jatuh_tempo.between' => 'Tanggal harus antara 1 dan 28.',
            'tanggal_jatuh_tempo.gte' => 'Jatuh tempo tidak boleh sebelum tanggal mulai bayar.',
        ]);

        $data['whatsapp_admin'] = $data['whatsapp_admin'] ? NomorHp::normalisasi($data['whatsapp_admin']) : null;
        $data['tanggal_mulai_bayar'] = (string) (int) $data['tanggal_mulai_bayar'];
        $data['tanggal_jatuh_tempo'] = (string) (int) $data['tanggal_jatuh_tempo'];

        $lama = Pengaturan::semua();
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
