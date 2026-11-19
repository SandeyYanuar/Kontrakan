<?php

namespace App\Enums;

/**
 * Status tagihan yang disimpan. Label waktu (jatuh tempo dekat, telat) dihitung saat ditampilkan.
 */
enum StatusTagihan: string
{
    case BelumBayar = 'belum_bayar';
    case Menunggu = 'menunggu';
    case Lunas = 'lunas';
    case Ditolak = 'ditolak';

    /**
     * Tagihan yang masih harus dibayar penyewa.
     */
    public function belumLunas(): bool
    {
        return $this === self::BelumBayar || $this === self::Ditolak;
    }
}
