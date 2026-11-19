<?php

namespace App\Support;

/**
 * Nomor HP WhatsApp Indonesia.
 *
 * Masukan boleh diawali 08, 62, atau +62, dengan spasi, strip, titik, atau kurung.
 * Disimpan dalam format 62 (misalnya 6281234567890) karena format itu yang dipakai link wa.me.
 */
class NomorHp
{
    /**
     * Ubah masukan menjadi format 62, atau null kalau bukan nomor HP Indonesia yang sah.
     */
    public static function normalisasi(string $masukan): ?string
    {
        $nomor = preg_replace('/[\s\-\.\(\)]/', '', trim($masukan));

        if (str_starts_with($nomor, '+62')) {
            $nomor = substr($nomor, 1);
        } elseif (str_starts_with($nomor, '08')) {
            $nomor = '62'.substr($nomor, 1);
        }

        if (preg_match('/^628\d{8,11}$/', $nomor) !== 1) {
            return null;
        }

        return $nomor;
    }

    /**
     * Tampilkan nomor format 62 sebagai 08xx-xxxx-xxxx.
     */
    public static function tampil(string $nomor): string
    {
        $lokal = '0'.substr($nomor, 2);

        return implode('-', array_filter([substr($lokal, 0, 4), substr($lokal, 4, 4), substr($lokal, 8)]));
    }

    /**
     * Link WhatsApp ke nomor ini dengan pesan yang sudah terisi.
     */
    public static function linkWhatsApp(string $nomor, string $pesan = ''): string
    {
        return 'https://wa.me/'.$nomor.($pesan !== '' ? '?text='.rawurlencode($pesan) : '');
    }
}
