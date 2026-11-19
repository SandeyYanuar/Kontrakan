<?php

namespace App\Support;

use App\Models\User;

/**
 * Kode unik penyewa: KT-0001, KT-0002, dan seterusnya.
 * Melekat ke orangnya, tidak ikut unit, dan tidak pernah dipakai ulang.
 */
class KodePenyewa
{
    public const AWALAN = 'KT-';

    /**
     * Kode berikutnya, dihitung dari kode terbesar yang pernah dipakai (termasuk penyewa yang sudah keluar).
     */
    public static function berikutnya(): string
    {
        $terakhir = User::query()
            ->whereNotNull('kode_penyewa')
            ->pluck('kode_penyewa')
            ->map(fn (string $kode): int => (int) substr($kode, strlen(self::AWALAN)))
            ->max() ?? 0;

        return self::AWALAN.str_pad((string) ($terakhir + 1), 4, '0', STR_PAD_LEFT);
    }
}
