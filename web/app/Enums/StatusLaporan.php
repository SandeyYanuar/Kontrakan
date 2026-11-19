<?php

namespace App\Enums;

/**
 * Tahap laporan kerusakan: diterima, dijadwalkan, dikerjakan, selesai.
 */
enum StatusLaporan: string
{
    case Diterima = 'diterima';
    case Dijadwalkan = 'dijadwalkan';
    case Dikerjakan = 'dikerjakan';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::Diterima => 'Laporan diterima',
            self::Dijadwalkan => 'Teknisi dijadwalkan',
            self::Dikerjakan => 'Sedang dikerjakan',
            self::Selesai => 'Selesai',
        };
    }

    public function urutan(): int
    {
        return array_search($this, self::cases(), true);
    }

    /**
     * Kategori kerusakan yang bisa dipilih penyewa.
     *
     * @return array<string, string>
     */
    public static function kategori(): array
    {
        return [
            'listrik' => 'Listrik',
            'air' => 'Air atau pipa',
            'ac' => 'AC',
            'furnitur' => 'Furnitur',
            'kebersihan' => 'Kebersihan',
            'lainnya' => 'Lainnya',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function waktu(): array
    {
        return ['pagi' => 'Pagi (08.00–11.00)', 'siang' => 'Siang (11.00–15.00)', 'sore' => 'Sore (15.00–18.00)'];
    }
}
