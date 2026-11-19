<?php

namespace App\Support;

use App\Models\Invoice;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Aturan tunggakan (keputusan Pangeran, 20 November 2026):
 * - Penyewa paling lama boleh menunggak 1 bulan. Kalau ada lebih dari 1 tagihan belum dibayar yang
 *   sudah lewat jatuh tempo, admin dan penyewa melihat peringatan "lewat batas tunggakan".
 * - Tagihan dibayar urut: bukti untuk satu bulan baru bisa diupload kalau bulan-bulan sebelumnya
 *   sudah lunas atau sedang dicek.
 */
class AturanTunggakan
{
    public const BATAS_BULAN = 1;

    /**
     * @param  Collection<int, Invoice>  $tagihan  tagihan milik satu penyewa
     */
    public static function jumlahTelat(Collection $tagihan, ?CarbonImmutable $hariIni = null): int
    {
        $hariIni = ($hariIni ?? CarbonImmutable::now())->startOfDay();

        return $tagihan
            ->filter(fn (Invoice $invoice): bool => $invoice->status->belumLunas() && $hariIni->greaterThan($invoice->jatuh_tempo))
            ->count();
    }

    /**
     * @param  Collection<int, Invoice>  $tagihan  tagihan milik satu penyewa
     */
    public static function lewatBatas(Collection $tagihan, ?CarbonImmutable $hariIni = null): bool
    {
        return self::jumlahTelat($tagihan, $hariIni) > self::BATAS_BULAN;
    }

    /**
     * Tagihan bulan sebelumnya yang harus dilunasi dulu sebelum tagihan ini bisa dibayar, atau null.
     */
    public static function penghalang(Invoice $invoice): ?Invoice
    {
        return Invoice::query()
            ->where('lease_id', $invoice->lease_id)
            ->where('periode', '<', $invoice->periode)
            ->whereIn('status', ['belum_bayar', 'ditolak'])
            ->orderBy('periode')
            ->first();
    }
}
