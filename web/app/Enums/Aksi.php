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

    case PenyewaDitambah = 'penyewa.ditambah';
    case PenyewaDiubah = 'penyewa.diubah';
    case PasswordPenyewaDireset = 'penyewa.password_direset';
    case PenyewaPindahUnit = 'penyewa.pindah_unit';
    case SewaDiakhiri = 'penyewa.sewa_diakhiri';

    case TagihanDibuatManual = 'tagihan.dibuat_manual';
    case PengingatWhatsApp = 'tagihan.pengingat_whatsapp';

    case BuktiDiupload = 'pembayaran.bukti_diupload';
    case PembayaranDisetujui = 'pembayaran.disetujui';
    case PembayaranDitolak = 'pembayaran.ditolak';
    case PersetujuanDibatalkan = 'pembayaran.persetujuan_dibatalkan';
    case NomorDaruratDiubah = 'akun.nomor_darurat_diubah';

    case LaporanDibuat = 'laporan.dibuat';
    case LaporanDijadwalkan = 'laporan.dijadwalkan';
    case LaporanDikerjakan = 'laporan.dikerjakan';
    case LaporanSelesai = 'laporan.selesai';
    case LaporanDikonfirmasi = 'laporan.dikonfirmasi';

    case PengumumanDibuat = 'pengumuman.dibuat';
    case PengumumanDiubah = 'pengumuman.diubah';
    case PengumumanDiakhiri = 'pengumuman.diakhiri';

    case KtpDiupload = 'ktp.diupload';
    case KtpDilihat = 'ktp.dilihat';
    case KtpDisahkan = 'ktp.disahkan';
    case KtpDitolak = 'ktp.ditolak';
    case KtpDihapus = 'ktp.dihapus';

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
            self::PenyewaDitambah => 'menambah penyewa',
            self::PenyewaDiubah => 'mengubah data penyewa',
            self::PasswordPenyewaDireset => 'mereset password penyewa',
            self::PenyewaPindahUnit => 'memindahkan penyewa ke unit lain',
            self::SewaDiakhiri => 'mengakhiri sewa',
            self::TagihanDibuatManual => 'membuat tagihan lebih awal',
            self::PengingatWhatsApp => 'membuka pengingat WhatsApp',
            self::BuktiDiupload => 'mengupload bukti transfer',
            self::PembayaranDisetujui => 'menyetujui pembayaran',
            self::PembayaranDitolak => 'menolak pembayaran',
            self::PersetujuanDibatalkan => 'membatalkan persetujuan pembayaran',
            self::NomorDaruratDiubah => 'mengubah nomor darurat',
            self::LaporanDibuat => 'melaporkan kerusakan',
            self::LaporanDijadwalkan => 'menjadwalkan perbaikan',
            self::LaporanDikerjakan => 'menandai perbaikan sedang dikerjakan',
            self::LaporanSelesai => 'menandai perbaikan selesai',
            self::LaporanDikonfirmasi => 'mengonfirmasi perbaikan sudah beres',
            self::PengumumanDibuat => 'menerbitkan pengumuman',
            self::PengumumanDiubah => 'mengubah pengumuman',
            self::PengumumanDiakhiri => 'mengakhiri pengumuman',
            self::KtpDiupload => 'mengupload KTP',
            self::KtpDilihat => 'membuka foto KTP',
            self::KtpDisahkan => 'menandai KTP sah',
            self::KtpDitolak => 'meminta upload ulang KTP',
            self::KtpDihapus => 'menghapus foto KTP',
        };
    }

    /**
     * Kelompok untuk filter jenis aktivitas dan ikon di halaman riwayat.
     */
    public function kategori(): string
    {
        return match ($this) {
            self::PembayaranDisetujui => 'disetujui',
            self::PembayaranDitolak, self::PersetujuanDibatalkan => 'ditolak',
            self::PengingatWhatsApp => 'pengingat',
            self::BuktiDiupload => 'bukti',
            self::LaporanDibuat, self::LaporanDijadwalkan, self::LaporanDikerjakan, self::LaporanSelesai, self::LaporanDikonfirmasi => 'laporan',
            self::PengumumanDibuat, self::PengumumanDiubah, self::PengumumanDiakhiri => 'pengumuman',
            self::KtpDiupload, self::KtpDilihat, self::KtpDisahkan, self::KtpDitolak, self::KtpDihapus => 'ktp',
            self::PenyewaDitambah, self::PenyewaDiubah, self::PasswordPenyewaDireset, self::PenyewaPindahUnit, self::SewaDiakhiri, self::TagihanDibuatManual => 'penyewa',
            default => 'pengaturan',
        };
    }

    /**
     * Pilihan filter jenis aktivitas.
     *
     * @return array<string, string>
     */
    public static function pilihanKategori(): array
    {
        return [
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak atau dibatalkan',
            'pengingat' => 'Pengingat WhatsApp',
            'bukti' => 'Bukti diupload',
            'laporan' => 'Laporan kerusakan',
            'pengumuman' => 'Pengumuman',
            'ktp' => 'KTP',
            'penyewa' => 'Penyewa dan tagihan',
            'pengaturan' => 'Unit, admin, dan pengaturan',
        ];
    }

    /**
     * @return list<self>
     */
    public static function dalamKategori(string $kategori): array
    {
        return array_values(array_filter(self::cases(), fn (self $aksi): bool => $aksi->kategori() === $kategori));
    }
}
