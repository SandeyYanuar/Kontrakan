<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Aksi;
use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Services\PembuatTagihan;
use App\Services\TagihanBulan;
use App\Support\Aktivitas;
use App\Support\BarisTagihan;
use App\Support\Format;
use App\Support\NomorHp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TagihanController extends Controller
{
    /**
     * Halaman tagihan. Tagihan yang sudah waktunya ikut dibuat saat halaman dibuka,
     * sebagai cadangan kalau penjadwal tidak berjalan di komputer lokal.
     */
    public function index(Request $request, PembuatTagihan $pembuat, TagihanBulan $tagihanBulan): View
    {
        $pembuat->buatOtomatis();

        return view('admin.tagihan', $tagihanBulan->untuk($this->periodeDipilih($request)));
    }

    /**
     * Catat pengingat lalu buka WhatsApp ke nomor penyewa dengan pesan yang sudah terisi.
     * Admin sendiri yang menekan kirim di WhatsApp.
     */
    public function ingatkan(Request $request, Lease $lease, TagihanBulan $tagihanBulan): RedirectResponse
    {
        abort_unless($lease->status === Lease::AKTIF, 404);

        /** @var BarisTagihan|null $baris */
        $baris = $tagihanBulan->untuk($this->periodeDipilih($request))['baris']
            ->first(fn (BarisTagihan $item): bool => $item->lease->is($lease));

        if (! $baris || $baris->harusDibayar()->isEmpty()) {
            return back()->with('status', "{$lease->penyewa->name} tidak punya tagihan yang perlu diingatkan.");
        }

        Aktivitas::catat(Aksi::PengingatWhatsApp, $lease->penyewa, [
            'kode_penyewa' => $lease->penyewa->kode_penyewa,
            'unit' => $lease->unit->kode_unit,
            'periode' => $baris->harusDibayar()->pluck('periode')->all(),
            'total' => $baris->totalHarusDibayar(),
            'status' => $baris->teksStatus(),
        ]);

        return redirect()->away(NomorHp::linkWhatsApp($lease->penyewa->phone, $baris->pesanPengingat(route('login'))));
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

    private function periodeDipilih(Request $request): string
    {
        $bulan = (string) $request->input('bulan');

        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan) === 1 ? $bulan : TagihanBulan::periodeAktif();
    }
}
