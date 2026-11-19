<?php

namespace App\Http\Controllers\Penyewa;

use App\Enums\Aksi;
use App\Enums\StatusTagihan;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Pengaturan;
use App\Rules\NomorHpIndonesia;
use App\Services\PembuatTagihan;
use App\Support\Aktivitas;
use App\Support\AturanTunggakan;
use App\Support\BarisTagihan;
use App\Support\NomorHp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman penyewa: beranda, riwayat bayar, dan profil.
 */
class BerandaController extends Controller
{
    public function beranda(Request $request, PembuatTagihan $pembuat): View
    {
        $pembuat->buatOtomatis();

        $penyewa = $request->user();
        $lease = $penyewa->leaseAktif()->with('unit')->first();

        $semua = $lease
            ? $lease->invoices()->with('paymentTerakhir')->orderBy('periode')->get()
            : collect();

        $terbuka = $semua->reject(fn (Invoice $invoice): bool => $invoice->status === StatusTagihan::Lunas)->values();
        $lunasTerakhir = $terbuka->isEmpty() ? $semua->last() : null;

        $statusPintu = $terbuka->isEmpty()
            ? ($lunasTerakhir ? Invoice::LABEL_LUNAS : BarisTagihan::TANPA_TAGIHAN)
            : $terbuka->map(fn (Invoice $invoice): string => $invoice->label())
                ->sortBy(fn (string $label): int => array_search($label, BarisTagihan::URUTAN, true))
                ->first();

        $nomorAdmin = Pengaturan::nomorWhatsAppAdmin();

        return view('penyewa.beranda', [
            'penyewa' => $penyewa,
            'lease' => $lease,
            'terbuka' => $terbuka,
            'lunasTerakhir' => $lunasTerakhir,
            'statusPintu' => $statusPintu,
            'pengaturan' => Pengaturan::semua(),
            'lewatBatas' => AturanTunggakan::lewatBatas($terbuka),
            'adaYangHarusDibayar' => $terbuka->contains(fn (Invoice $invoice): bool => $invoice->bisaDibayar()),
            'linkPengelola' => $nomorAdmin
                ? NomorHp::linkWhatsApp($nomorAdmin, "Halo, saya {$penyewa->name} ({$penyewa->kode_penyewa}) unit ".($lease?->unit->kode_unit ?? '-').'. ')
                : null,
        ]);
    }

    public function riwayat(Request $request): View
    {
        $lunas = Invoice::query()
            ->whereIn('lease_id', $request->user()->leases()->pluck('id'))
            ->where('status', StatusTagihan::Lunas)
            ->with('lease.unit', 'paymentTerakhir')
            ->orderByDesc('periode')
            ->get();

        return view('penyewa.riwayat', ['lunas' => $lunas]);
    }

    public function profil(Request $request): View
    {
        return view('penyewa.profil', ['penyewa' => $request->user()]);
    }

    public function simpanDarurat(Request $request): RedirectResponse
    {
        $data = $request->validate(
            ['phone_darurat' => ['nullable', new NomorHpIndonesia]],
        );

        $penyewa = $request->user();
        $penyewa->phone_darurat = ($data['phone_darurat'] ?? null) ? NomorHp::normalisasi($data['phone_darurat']) : null;

        if ($penyewa->isDirty('phone_darurat')) {
            $penyewa->save();
            Aktivitas::catat(Aksi::NomorDaruratDiubah, $penyewa, ['kode_penyewa' => $penyewa->kode_penyewa]);
        }

        return redirect()->route('penyewa.profil')->with('status', 'Nomor darurat disimpan.');
    }
}
