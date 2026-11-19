<?php

namespace App\Support;

use App\Enums\Aksi;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Menyusun kalimat yang mudah dibaca dari satu catatan riwayat aktivitas.
 */
class KalimatAktivitas
{
    /**
     * Label untuk kunci pengaturan saat pengaturan diubah.
     *
     * @var array<string, string>
     */
    private const LABEL_PENGATURAN = [
        'nama_kontrakan' => 'nama kontrakan',
        'bank_nama' => 'nama bank',
        'bank_nomor' => 'nomor rekening',
        'bank_atas_nama' => 'nama pemilik rekening',
        'whatsapp_admin' => 'nomor WhatsApp pengelola',
        'tanggal_mulai_bayar' => 'tanggal mulai bayar',
        'tanggal_jatuh_tempo' => 'tanggal jatuh tempo',
    ];

    public function __construct(private readonly ActivityLog $log) {}

    /**
     * Nama pelaku, "Sistem" kalau tidak ada.
     */
    public function pelaku(): string
    {
        return $this->log->actor?->name ?? 'Sistem';
    }

    /**
     * Keterangan peran pelaku, misalnya kode penyewa kalau pelakunya penyewa.
     */
    public function keteranganPelaku(): ?string
    {
        $actor = $this->log->actor;

        return $actor?->isPenyewa() ? $actor->kode_penyewa : null;
    }

    /**
     * Kalimat setelah nama pelaku, misalnya "menyetujui pembayaran A3 Siti Aminah (KT-0007) sebesar Rp 750.000 untuk Desember 2026".
     */
    public function kalimat(): string
    {
        $aksi = $this->log->aksi;
        $data = $this->log->data ?? [];

        $detail = match ($aksi) {
            Aksi::BuktiDiupload => 'unit '.($data['unit'] ?? '')
                .(isset($data['nominal']) ? ' sebesar '.Format::rupiah($data['nominal']) : '')
                .(isset($data['periode']) ? ' untuk '.Format::bulan($data['periode']) : ''),
            Aksi::PembayaranDisetujui, Aksi::PembayaranDitolak, Aksi::PersetujuanDibatalkan => $this->objekPenyewa()
                .(isset($data['nominal']) ? ' sebesar '.Format::rupiah($data['nominal']) : '')
                .(isset($data['periode']) ? ' untuk '.Format::bulan($data['periode']) : ''),
            Aksi::PengingatWhatsApp => 'untuk '.$this->objekPenyewa()
                .(isset($data['total']) ? ', total '.Format::rupiah($data['total']) : '')
                .(isset($data['status']) ? ' ('.mb_strtolower($data['status']).')' : ''),
            Aksi::TagihanDibuatManual => $this->objekPenyewa().(isset($data['periode']) ? ' untuk '.Format::bulan($data['periode']) : ''),
            Aksi::UnitDibuat => ($data['kode_unit'] ?? '').(isset($data['harga_sewa']) ? ' seharga '.Format::rupiah($data['harga_sewa']).' per bulan' : ''),
            Aksi::UnitDiubah => $data['kode_unit'] ?? '',
            Aksi::HargaUnitDiubah => ($data['kode_unit'] ?? '').' dari '.Format::rupiah($data['harga_lama'] ?? 0).' menjadi '.Format::rupiah($data['harga_baru'] ?? 0),
            Aksi::AdminDibuat, Aksi::AdminDinonaktifkan, Aksi::AdminDiaktifkan, Aksi::PasswordAdminDireset => $data['nama'] ?? $this->namaSubjek(),
            Aksi::PenyewaPindahUnit => $this->objekPenyewa().' dari '.($data['dari'] ?? '').' ke '.($data['ke'] ?? ''),
            Aksi::SewaDiakhiri => $this->objekPenyewa().' di unit '.($data['unit'] ?? '')
                .(isset($data['tgl_selesai']) ? ' per '.Format::tanggal(CarbonImmutable::parse($data['tgl_selesai'])) : ''),
            Aksi::PengaturanDiubah => implode(', ', array_map(fn (string $kunci): string => self::LABEL_PENGATURAN[$kunci] ?? $kunci, array_keys($data))),
            Aksi::LaporanDibuat => ($data['kategori'] ?? '').' di unit '.($data['unit'] ?? ''),
            Aksi::LaporanDijadwalkan, Aksi::LaporanDikerjakan, Aksi::LaporanSelesai, Aksi::LaporanDikonfirmasi => ($data['kategori'] ?? '').' '.$this->objekPenyewa()
                .(isset($data['jadwal']) ? ' pada '.$data['jadwal'] : ''),
            Aksi::PengumumanDibuat, Aksi::PengumumanDiubah, Aksi::PengumumanDiakhiri => '"'.($data['judul'] ?? '').'"',
            Aksi::PasswordDiganti, Aksi::NomorDaruratDiubah, Aksi::KtpDiupload => '',
            default => $this->objekPenyewa(),
        };

        return trim($aksi->label().' '.$detail);
    }

    /**
     * Alasan penolakan atau pembatalan, kalau ada.
     */
    public function alasan(): ?string
    {
        return $this->log->data['alasan'] ?? null;
    }

    /**
     * Pembayaran yang buktinya bisa dilihat dari catatan ini.
     */
    public function pembayaran(): ?Payment
    {
        $subjek = $this->log->subject;

        return match (true) {
            $subjek instanceof Payment => $subjek,
            $subjek instanceof Invoice && $this->log->aksi === Aksi::BuktiDiupload => $subjek->paymentTerakhir,
            default => null,
        };
    }

    /**
     * Penyewa yang terkait, ditulis "A3 Siti Aminah (KT-0007)".
     */
    private function objekPenyewa(): string
    {
        $data = $this->log->data ?? [];
        $nama = $this->namaPenyewa();
        $unit = $data['unit'] ?? null;
        $kode = $data['kode_penyewa'] ?? null;

        return trim(($unit ? $unit.' ' : '').($nama ?? '').($kode ? " ({$kode})" : ''));
    }

    private function namaPenyewa(): ?string
    {
        $subjek = $this->log->subject;

        return match (true) {
            $subjek instanceof User => $subjek->name,
            $subjek instanceof Payment => $subjek->invoice?->lease?->penyewa?->name,
            $subjek instanceof Invoice => $subjek->lease?->penyewa?->name,
            default => $this->log->data['nama'] ?? null,
        };
    }

    private function namaSubjek(): string
    {
        return $this->log->subject instanceof User ? $this->log->subject->name : '';
    }
}
