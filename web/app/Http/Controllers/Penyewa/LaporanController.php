<?php

namespace App\Http\Controllers\Penyewa;

use App\Enums\Aksi;
use App\Enums\StatusLaporan;
use App\Http\Controllers\Controller;
use App\Models\MaintenanceTicket;
use App\Support\Aktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Penyewa melaporkan kerusakan dan memantau perbaikannya.
 */
class LaporanController extends Controller
{
    public function index(Request $request): View
    {
        $laporan = MaintenanceTicket::query()
            ->whereIn('lease_id', $request->user()->leases()->pluck('id'))
            ->with('media', 'lease.unit')
            ->latest()
            ->get();

        return view('penyewa.laporan.index', ['laporan' => $laporan]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->user()->leaseAktif) {
            return redirect()->route('penyewa.beranda');
        }

        return view('penyewa.laporan.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $penyewa = $request->user();
        $lease = $penyewa->leaseAktif()->with('unit')->firstOrFail();

        $data = $request->validate([
            'kategori' => ['required', Rule::in(array_keys(StatusLaporan::kategori()))],
            'deskripsi' => ['required', 'string', 'max:1000'],
            'foto' => ['nullable', 'array', 'max:3'],
            'foto.*' => ['image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'preferensi_tanggal' => ['nullable', 'date', 'after_or_equal:today'],
            'preferensi_waktu' => ['nullable', Rule::in(array_keys(StatusLaporan::waktu()))],
        ], [
            'kategori.required' => 'Pilih jenis kerusakan.',
            'kategori.in' => 'Pilih jenis kerusakan.',
            'deskripsi.required' => 'Ceritakan kerusakannya.',
            'foto.max' => 'Paling banyak 3 foto.',
            'foto.*.image' => 'Foto harus berupa gambar JPG atau PNG.',
            'foto.*.mimes' => 'Foto harus berupa gambar JPG atau PNG.',
            'foto.*.max' => 'Ukuran setiap foto maksimal 2 MB.',
            'preferensi_tanggal.after_or_equal' => 'Pilih tanggal hari ini atau sesudahnya.',
        ]);

        $laporan = DB::transaction(function () use ($request, $lease, $data, $penyewa): MaintenanceTicket {
            $laporan = $lease->laporan()->create([
                'kategori' => $data['kategori'],
                'deskripsi' => $data['deskripsi'],
                'preferensi_tanggal' => $data['preferensi_tanggal'] ?? null,
                'preferensi_waktu' => $data['preferensi_waktu'] ?? null,
                'status' => StatusLaporan::Diterima,
            ]);

            foreach ($request->file('foto', []) as $foto) {
                $laporan->media()->create(['file_path' => $foto->store('laporan/'.$laporan->id, 'local'), 'jenis' => 'foto']);
            }

            Aktivitas::catat(Aksi::LaporanDibuat, $laporan, [
                'kode_penyewa' => $penyewa->kode_penyewa,
                'unit' => $lease->unit->kode_unit,
                'kategori' => $laporan->labelKategori(),
            ]);

            return $laporan;
        });

        return redirect()->route('penyewa.laporan.index')->with('status', 'Laporan terkirim. Pengelola akan menjadwalkan perbaikan.');
    }

    public function konfirmasi(Request $request, MaintenanceTicket $laporan): RedirectResponse
    {
        abort_unless($laporan->lease->user_id === $request->user()->id, 404);

        if ($laporan->status !== StatusLaporan::Selesai || $laporan->dikonfirmasi_at) {
            return back();
        }

        $laporan->update(['dikonfirmasi_at' => now()]);
        Aktivitas::catat(Aksi::LaporanDikonfirmasi, $laporan, [
            'kode_penyewa' => $request->user()->kode_penyewa,
            'unit' => $laporan->lease->unit->kode_unit,
            'kategori' => $laporan->labelKategori(),
        ]);

        return redirect()->route('penyewa.laporan.index')->with('status', 'Terima kasih, perbaikan ditandai sudah beres.');
    }
}
