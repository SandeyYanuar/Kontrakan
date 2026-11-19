<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Aksi;
use App\Enums\StatusLaporan;
use App\Http\Controllers\Controller;
use App\Models\MaintenanceTicket;
use App\Support\Aktivitas;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin menangani laporan kerusakan: jadwalkan kunjungan, tandai dikerjakan dan selesai.
 */
class LaporanController extends Controller
{
    public function index(): View
    {
        $laporan = MaintenanceTicket::query()
            ->with('lease.unit', 'lease.penyewa', 'media')
            ->latest()
            ->get()
            ->sortBy(fn (MaintenanceTicket $l): int => $l->aktif() ? $l->status->urutan() : 10)
            ->values();

        return view('admin.laporan.index', ['laporan' => $laporan]);
    }

    public function show(MaintenanceTicket $laporan): View
    {
        return view('admin.laporan.show', ['laporan' => $laporan->load('lease.unit', 'lease.penyewa', 'media')]);
    }

    public function jadwal(Request $request, MaintenanceTicket $laporan): RedirectResponse
    {
        $data = $request->validate([
            'tanggal' => ['required', 'date', 'after_or_equal:today'],
            'jam' => ['required', 'date_format:H:i'],
        ], [
            'tanggal.required' => 'Pilih tanggal kunjungan.',
            'tanggal.after_or_equal' => 'Pilih tanggal hari ini atau sesudahnya.',
            'jam.required' => 'Isi jam kunjungan.',
        ]);

        $jadwal = CarbonImmutable::parse($data['tanggal'].' '.$data['jam']);
        $laporan->update(['jadwal_kunjungan' => $jadwal, 'status' => StatusLaporan::Dijadwalkan]);

        $this->catat(Aksi::LaporanDijadwalkan, $laporan, ['jadwal' => $jadwal->locale('id')->translatedFormat('l, j F').' pukul '.$jadwal->format('H.i')]);

        return redirect()->route('admin.laporan.show', $laporan)->with('status', 'Kunjungan dijadwalkan. Kabari penyewa lewat WhatsApp.');
    }

    public function status(Request $request, MaintenanceTicket $laporan): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['dikerjakan', 'selesai'])]]);
        $status = StatusLaporan::from($data['status']);

        $laporan->update([
            'status' => $status,
            'selesai_at' => $status === StatusLaporan::Selesai ? now() : null,
        ]);

        $this->catat($status === StatusLaporan::Selesai ? Aksi::LaporanSelesai : Aksi::LaporanDikerjakan, $laporan);

        return redirect()->route('admin.laporan.show', $laporan)
            ->with('status', $status === StatusLaporan::Selesai
                ? 'Perbaikan ditandai selesai. Penyewa bisa mengonfirmasi dari HP-nya.'
                : 'Perbaikan ditandai sedang dikerjakan.');
    }

    /**
     * @param  array<string, string>  $tambahan
     */
    private function catat(Aksi $aksi, MaintenanceTicket $laporan, array $tambahan = []): void
    {
        Aktivitas::catat($aksi, $laporan, [
            'kode_penyewa' => $laporan->lease->penyewa->kode_penyewa,
            'unit' => $laporan->lease->unit->kode_unit,
            'kategori' => $laporan->labelKategori(),
            ...$tambahan,
        ]);
    }
}
