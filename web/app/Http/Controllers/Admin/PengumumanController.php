<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Aksi;
use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use App\Support\Aktivitas;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pengumuman untuk penyewa, tampil di beranda mereka selama periode tampilnya.
 */
class PengumumanController extends Controller
{
    public function index(): View
    {
        $pengumuman = Pengumuman::query()->with('pembuat')->orderByDesc('tampil_mulai')->get();

        return view('admin.pengumuman.index', ['kelompok' => $pengumuman->groupBy(fn (Pengumuman $p): string => $p->keadaan())]);
    }

    public function create(): View
    {
        return view('admin.pengumuman.form', ['pengumuman' => new Pengumuman(['tampil_mulai' => CarbonImmutable::today()])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $pengumuman = Pengumuman::create([...$this->validasi($request), 'created_by' => $request->user()->id]);
        Aktivitas::catat(Aksi::PengumumanDibuat, $pengumuman, ['judul' => $pengumuman->judul]);

        return redirect()->route('admin.pengumuman.index')->with('status', 'Pengumuman diterbitkan.');
    }

    public function edit(Pengumuman $pengumuman): View
    {
        return view('admin.pengumuman.form', ['pengumuman' => $pengumuman]);
    }

    public function update(Request $request, Pengumuman $pengumuman): RedirectResponse
    {
        $pengumuman->update($this->validasi($request));
        Aktivitas::catat(Aksi::PengumumanDiubah, $pengumuman, ['judul' => $pengumuman->judul]);

        return redirect()->route('admin.pengumuman.index')->with('status', 'Pengumuman disimpan.');
    }

    /**
     * Berhenti menampilkan pengumuman mulai hari ini. Catatannya tetap disimpan.
     */
    public function akhiri(Pengumuman $pengumuman): RedirectResponse
    {
        $kemarin = CarbonImmutable::yesterday();
        $pengumuman->update([
            'tampil_sampai' => $kemarin,
            'tampil_mulai' => $pengumuman->tampil_mulai->greaterThan($kemarin) ? $kemarin : $pengumuman->tampil_mulai,
        ]);
        Aktivitas::catat(Aksi::PengumumanDiakhiri, $pengumuman, ['judul' => $pengumuman->judul]);

        return redirect()->route('admin.pengumuman.index')->with('status', 'Pengumuman tidak lagi tampil di beranda penyewa.');
    }

    /**
     * @return array{judul: string, isi: string, tampil_mulai: string, tampil_sampai: ?string}
     */
    private function validasi(Request $request): array
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:120'],
            'isi' => ['required', 'string', 'max:2000'],
            'tampil_mulai' => ['required', 'date'],
            'tampil_sampai' => ['nullable', 'date', 'after_or_equal:tampil_mulai'],
        ], [
            'judul.required' => 'Judul wajib diisi.',
            'isi.required' => 'Isi pengumuman wajib diisi.',
            'tampil_mulai.required' => 'Pilih tanggal mulai tampil.',
            'tampil_sampai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ]);

        $data['tampil_sampai'] ??= null;

        return $data;
    }
}
