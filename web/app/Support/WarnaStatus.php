<?php

namespace App\Support;

use App\Models\Invoice;

/**
 * Kelas warna Tailwind untuk setiap status tagihan (pintu, kotak unit, dan label).
 */
class WarnaStatus
{
    public static function latar(string $status): string
    {
        return match ($status) {
            Invoice::LABEL_LUNAS => 'bg-lunas',
            Invoice::LABEL_MENUNGGU => 'bg-dicek',
            Invoice::LABEL_DEKAT => 'bg-dekat',
            Invoice::LABEL_TELAT, Invoice::LABEL_DITOLAK => 'bg-telat',
            default => 'bg-belum',
        };
    }

    public static function label(string $status): string
    {
        return match ($status) {
            Invoice::LABEL_LUNAS => 'bg-lunas-bg text-lunas',
            Invoice::LABEL_MENUNGGU => 'bg-dicek-bg text-dicek',
            Invoice::LABEL_DEKAT => 'bg-dekat-bg text-dekat',
            Invoice::LABEL_TELAT, Invoice::LABEL_DITOLAK => 'bg-telat-bg text-telat',
            default => 'bg-soft text-muted',
        };
    }

    public static function teks(string $status): string
    {
        return match ($status) {
            Invoice::LABEL_LUNAS => 'text-lunas',
            Invoice::LABEL_MENUNGGU => 'text-dicek',
            Invoice::LABEL_DEKAT => 'text-dekat',
            Invoice::LABEL_TELAT, Invoice::LABEL_DITOLAK => 'text-telat',
            default => 'text-muted',
        };
    }
}
