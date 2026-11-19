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
     * Ambil angka dari ketikan nominal, misalnya "Rp 850.000" menjadi "850000".
     */
    public static function angka(?string $ketikan): string
    {
        return preg_replace('/\D/', '', (string) $ketikan);
    }

    /**
     * Nominal dalam kata untuk kuitansi, misalnya "tujuh ratus lima puluh ribu rupiah".
     */
    public static function terbilang(int $angka): string
    {
        return ($angka === 0 ? 'nol' : self::kata($angka)).' rupiah';
    }

    private static function kata(int $angka): string
    {
        $satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

        return trim(match (true) {
            $angka < 12 => $satuan[$angka],
            $angka < 20 => self::kata($angka - 10).' belas',
            $angka < 100 => self::kata(intdiv($angka, 10)).' puluh '.self::kata($angka % 10),
            $angka < 200 => 'seratus '.self::kata($angka - 100),
            $angka < 1000 => self::kata(intdiv($angka, 100)).' ratus '.self::kata($angka % 100),
            $angka < 2000 => 'seribu '.self::kata($angka - 1000),
            $angka < 1000000 => self::kata(intdiv($angka, 1000)).' ribu '.self::kata($angka % 1000),
            $angka < 1000000000 => self::kata(intdiv($angka, 1000000)).' juta '.self::kata($angka % 1000000),
            default => self::kata(intdiv($angka, 1000000000)).' miliar '.self::kata($angka % 1000000000),
        });
    }

    /**
     * NIK tersamar: 3 digit depan dan 3 digit belakang terlihat, misalnya "320••••••••••004".
     */
    public static function nikTersamar(?string $nik): string
    {
        if ($nik === null || strlen($nik) < 7) {
            return '';
        }

        return substr($nik, 0, 3).str_repeat('•', strlen($nik) - 6).substr($nik, -3);
    }

    /**
     * Format tanggal lengkap, misalnya "5 Desember 2026".
     */
    public static function tanggal(CarbonInterface $tanggal): string
    {
        return $tanggal->locale('id')->translatedFormat('j F Y');
    }

    /**
     * Format tanggal dan nama bulan tanpa tahun, misalnya "5 Desember".
     */
    public static function tanggalBulan(CarbonInterface $tanggal): string
    {
        return $tanggal->locale('id')->translatedFormat('j F');
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
