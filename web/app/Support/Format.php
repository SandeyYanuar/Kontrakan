<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Format tampilan untuk antarmuka berbahasa Indonesia.
 */
class Format
{
    /**
     * Format nominal Rupiah tanpa desimal, misalnya "Rp 750.000".
     */
    public static function rupiah(int $nominal): string
    {
        $angka = number_format(abs($nominal), 0, ',', '.');

        return ($nominal < 0 ? '-' : '').'Rp '.$angka;
    }

    /**
     * Format tanggal lengkap, misalnya "5 Desember 2026".
     */
    public static function tanggal(CarbonInterface $tanggal): string
    {
        return $tanggal->locale('id')->translatedFormat('j F Y');
    }

    /**
     * Format tanggal pendek tanpa tahun, misalnya "18 Nov".
     */
    public static function tanggalPendek(CarbonInterface $tanggal): string
    {
        return $tanggal->locale('id')->translatedFormat('j M');
    }

    /**
     * Format periode tagihan "YYYY-MM" menjadi nama bulan, misalnya "Desember 2026".
     */
    public static function bulan(string $periode): string
    {
        return CarbonImmutable::createFromFormat('!Y-m', $periode)->locale('id')->translatedFormat('F Y');
    }
}
