<?php

namespace App\Enums;

/**
 * Jenis aktivitas yang dicatat di riwayat aktivitas.
 * Langkah berikutnya menambah jenis baru (tagihan, pembayaran, pengingat WhatsApp, KTP).
 */
enum Aksi: string
{
    case PengaturanDiubah = 'pengaturan.diubah';

    case UnitDibuat = 'unit.dibuat';
    case UnitDiubah = 'unit.diubah';
    case HargaUnitDiubah = 'unit.harga_diubah';

    case AdminDibuat = 'admin.dibuat';
    case AdminDinonaktifkan = 'admin.dinonaktifkan';
    case AdminDiaktifkan = 'admin.diaktifkan';
    case PasswordAdminDireset = 'admin.password_direset';

    case PasswordDiganti = 'akun.password_diganti';

    /**
     * Kalimat singkat untuk halaman riwayat aktivitas.
     */
    public function label(): string
    {
        return match ($this) {
            self::PengaturanDiubah => 'mengubah pengaturan',
            self::UnitDibuat => 'menambah unit',
            self::UnitDiubah => 'mengubah unit',
            self::HargaUnitDiubah => 'mengubah harga sewa unit',
            self::AdminDibuat => 'menambah admin',
            self::AdminDinonaktifkan => 'menonaktifkan admin',
            self::AdminDiaktifkan => 'mengaktifkan kembali admin',
            self::PasswordAdminDireset => 'mereset password admin',
            self::PasswordDiganti => 'mengganti password sendiri',
        };
    }
}
