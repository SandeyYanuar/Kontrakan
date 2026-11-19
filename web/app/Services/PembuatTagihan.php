<?php

namespace App\Services;

use App\Enums\StatusTagihan;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Lease;
use App\Support\Format;
use App\Support\PeriodeBayar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Membuat tagihan sewa bulanan.
 *
 * Aturan:
 * - Tagihan periode P dibuat 14 hari sebelum jatuh tempo P.
 * - Tagihan pertama adalah bulan setelah bulan mulai sewa (bulan masuk dibayar lewat booking di luar aplikasi).
 * - Penyewa lama yang baru dimasukkan ke aplikasi tidak ditagih untuk periode yang jatuh temponya
 *   sebelum kontrak dicatat di aplikasi.
 * - Aman dijalankan berulang: tidak pernah membuat tagihan ganda.
 * - Nominal memakai harga unit saat tagihan dibuat.
 */
class PembuatTagihan
{
    /**
     * Buat semua tagihan yang sudah waktunya dibuat sampai hari ini. Mengembalikan jumlah tagihan baru.
     */
    public function buatOtomatis(?CarbonImmutable $hariIni = null): int
    {
        $hariIni = ($hariIni ?? CarbonImmutable::now())->startOfDay();
        $jumlah = 0;

        Lease::query()->aktif()->with('unit')->each(function (Lease $lease) use ($hariIni, &$jumlah): void {
            $periode = $this->periodePertama($lease);

            while (PeriodeBayar::tanggalDibuat($periode)->lessThanOrEqualTo($hariIni)) {
                if ($this->buat($lease, $periode)) {
                    $jumlah++;
                }

                $periode = PeriodeBayar::berikutnya($periode);
            }
        });

        return $jumlah;
    }

    /**
     * Buat tagihan periode berikutnya untuk satu kontrak lebih awal (misalnya penyewa ingin bayar di muka).
     */
    public function buatBulanBerikutnya(Lease $lease): Invoice
    {
        $terakhir = $lease->invoices()->max('periode');
        $periode = $terakhir ? PeriodeBayar::berikutnya($terakhir) : $this->periodePertama($lease);

        return $this->buat($lease, $periode) ?? $lease->invoices()->where('periode', $periode)->sole();
    }

    /**
     * Periode pertama yang ditagih untuk kontrak ini.
     */
    public function periodePertama(Lease $lease): string
    {
        $periode = PeriodeBayar::berikutnya(PeriodeBayar::dari($lease->tgl_mulai));
        $dicatat = CarbonImmutable::parse($lease->created_at)->startOfDay();

        while (PeriodeBayar::jatuhTempo($periode)->lessThan($dicatat)) {
            $periode = PeriodeBayar::berikutnya($periode);
        }

        return $periode;
    }

    /**
     * Buat tagihan satu periode, atau null kalau sudah ada.
     */
    private function buat(Lease $lease, string $periode): ?Invoice
    {
        if ($lease->invoices()->where('periode', $periode)->exists()) {
            return null;
        }

        return DB::transaction(function () use ($lease, $periode): Invoice {
            $harga = $lease->unit->harga_sewa;

            $invoice = $lease->invoices()->create([
                'periode' => $periode,
                'jatuh_tempo' => PeriodeBayar::jatuhTempo($periode),
                'total' => $harga,
                'status' => StatusTagihan::BelumBayar,
            ]);

            $invoice->items()->create([
                'jenis' => InvoiceItem::SEWA,
                'keterangan' => 'Sewa '.Format::bulan($periode).' unit '.$lease->unit->kode_unit,
                'nominal' => $harga,
            ]);

            return $invoice;
        });
    }
}
