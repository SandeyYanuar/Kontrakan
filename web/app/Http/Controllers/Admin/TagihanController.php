<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Aksi;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Lease;
use App\Services\PembuatTagihan;
use App\Support\Aktivitas;
use App\Support\Format;
use App\Support\PeriodeBayar;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TagihanController extends Controller
{
    /**
     * Halaman tagihan bulan berjalan. Tagihan yang sudah waktunya ikut dibuat saat halaman dibuka,
     * sebagai cadangan kalau penjadwal tidak berjalan di komputer lokal.
     */
    public function index(Request $request, PembuatTagihan $pembuat): View
    {
        $pembuat->buatOtomatis();

        $periode = $request->string('bulan')->isMatch('/^\d{4}-\d{2}$/')
            ? (string) $request->string('bulan')
            : PeriodeBayar::dari(CarbonImmutable::now());

        $tagihan = Invoice::query()
            ->where('periode', $periode)
            ->with('lease.unit', 'lease.penyewa')
            ->get();

        return view('admin.tagihan', ['periode' => $periode, 'tagihan' => $tagihan]);
    }

    /**
     * Buat tagihan bulan berikutnya lebih awal untuk satu kontrak sewa.
     */
    public function buatBulanBerikutnya(Lease $lease, PembuatTagihan $pembuat): RedirectResponse
    {
        abort_unless($lease->status === Lease::AKTIF, 404);

        $jumlahSebelum = $lease->invoices()->count();
        $invoice = $pembuat->buatBulanBerikutnya($lease);

        if ($lease->invoices()->count() > $jumlahSebelum) {
            Aktivitas::catat(Aksi::TagihanDibuatManual, $invoice, [
                'kode_penyewa' => $lease->penyewa->kode_penyewa,
                'unit' => $lease->unit->kode_unit,
                'periode' => $invoice->periode,
                'nominal' => $invoice->total,
            ]);
        }

        return back()->with('status', 'Tagihan '.Format::bulan($invoice->periode)." untuk {$lease->penyewa->name} sudah dibuat.");
    }
}
