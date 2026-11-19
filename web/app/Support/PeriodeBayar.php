<?php

namespace App\Support;

use App\Models\Pengaturan;
use Carbon\CarbonImmutable;

/**
 * Hitungan tanggal untuk periode tagihan "YYYY-MM", mengikuti Pengaturan (bawaan: 27 bulan sebelumnya sampai 5).
 */
class PeriodeBayar
{
    /**
     * Jumlah hari sebelum jatuh tempo saat tagihan dibuat otomatis.
     */
    public const HARI_DIBUAT_SEBELUM_JATUH_TEMPO = 14;

    public static function jatuhTempo(string $periode): CarbonImmutable
    {
        return self::awalBulan($periode)->setDay((int) Pengaturan::ambil('tanggal_jatuh_tempo'));
    }

    /**
     * Tanggal mulai bayar. Kalau tanggal mulai lebih besar dari jatuh tempo, jatuh di bulan sebelumnya.
     */
    public static function mulaiBayar(string $periode): CarbonImmutable
    {
        $mulai = (int) Pengaturan::ambil('tanggal_mulai_bayar');
        $jatuhTempo = (int) Pengaturan::ambil('tanggal_jatuh_tempo');
        $bulan = self::awalBulan($periode);

        return ($mulai > $jatuhTempo ? $bulan->subMonth() : $bulan)->setDay($mulai);
    }

    /**
     * Tanggal tagihan periode ini dibuat otomatis.
     */
    public static function tanggalDibuat(string $periode): CarbonImmutable
    {
        return self::jatuhTempo($periode)->subDays(self::HARI_DIBUAT_SEBELUM_JATUH_TEMPO);
    }

    public static function berikutnya(string $periode): string
    {
        return self::awalBulan($periode)->addMonth()->format('Y-m');
    }

    public static function dari(CarbonImmutable $tanggal): string
    {
        return $tanggal->format('Y-m');
    }

    public static function awalBulan(string $periode): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m', $periode);
    }
}
