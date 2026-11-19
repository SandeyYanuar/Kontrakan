<?php

namespace Database\Seeders;

use App\Enums\Aksi;
use App\Enums\Peran;
use App\Enums\StatusLaporan;
use App\Enums\StatusTagihan;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Pengaturan;
use App\Models\TenantIdentity;
use App\Models\Unit;
use App\Models\User;
use App\Services\TagihanBulan;
use App\Support\Aktivitas;
use App\Support\Format;
use App\Support\PeriodeBayar;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Data contoh untuk mencoba aplikasi: 9 unit, 8 penyewa, dan laporan kerusakan dengan status yang berbeda-beda.
 * Semua tanggal dihitung dari hari ini, jadi statusnya tetap bervariasi kapan pun seeder dijalankan.
 *
 * Jalankan HANYA ke database contoh: php artisan db:seed --class=DemoSeeder --env=demo
 *
 * Password: penyewa "penyewa123", admin Dewi "admin12345", super admin dari DatabaseSeeder.
 */
class DemoSeeder extends Seeder
{
    private User $superAdmin;

    private User $dewi;

    private string $bulanIni;

    private string $bulanLalu;

    private string $duaBulanLalu;

    public function run(): void
    {
        $this->call(DatabaseSeeder::class);

        $hariIni = CarbonImmutable::now();
        $this->bulanIni = TagihanBulan::periodeAktif($hariIni);
        $this->bulanLalu = PeriodeBayar::awalBulan($this->bulanIni)->subMonth()->format('Y-m');
        $this->duaBulanLalu = PeriodeBayar::awalBulan($this->bulanIni)->subMonths(2)->format('Y-m');

        Pengaturan::simpan([
            'nama_kontrakan' => 'Kontrakan Gunung Kawi',
            'bank_nama' => 'Bank Contoh',
            'bank_nomor' => '1234 5678 4821',
            'bank_atas_nama' => 'Pangeran G. K.',
        ]);

        $this->superAdmin = User::query()->where('role', Peran::SuperAdmin)->oldest('id')->firstOrFail();
        $this->dewi = User::query()->updateOrCreate(['phone' => '6281300000099'], [
            'name' => 'Dewi',
            'password' => 'admin12345',
            'role' => Peran::Admin,
            'is_active' => true,
        ]);

        $units = collect([
            ['A1', 750000], ['A2', 750000], ['A3', 750000], ['A4', 750000],
            ['B1', 850000], ['B2', 850000], ['B3', 850000], ['B4', 850000], ['C1', 950000],
        ])->mapWithKeys(fn (array $u) => [$u[0] => Unit::query()->updateOrCreate(['kode_unit' => $u[0]], ['harga_sewa' => $u[1]])]);

        $mulaiLama = PeriodeBayar::awalBulan($this->duaBulanLalu)->subMonth()->setDay(10);

        // 1. Bambang: semua lunas, KTP sah.
        $bambang = $this->penyewa('Bambang Santoso', 'KT-0001', '6281300000001', $units['A1'], $mulaiLama);
        $this->ktp($bambang, TenantIdentity::SAH);
        $this->tagihanLunas($bambang, [$this->duaBulanLalu, $this->bulanLalu, $this->bulanIni]);

        // 2. Yuliani: bulan ini belum bayar (jatuh tempo dekat / telat tergantung tanggal), KTP menunggu dicek.
        $yuliani = $this->penyewa('Yuliani', 'KT-0002', '6281300000002', $units['A2'], $mulaiLama);
        $this->ktp($yuliani, TenantIdentity::MENUNGGU);
        $this->tagihanLunas($yuliani, [$this->duaBulanLalu, $this->bulanLalu]);
        $this->tagihan($yuliani, $this->bulanIni);

        // 3. Siti: bulan ini sudah upload bukti, menunggu dicek admin. KTP sah.
        $siti = $this->penyewa('Siti Aminah', 'KT-0003', '6281300000003', $units['A3'], $mulaiLama);
        $this->ktp($siti, TenantIdentity::SAH);
        $this->tagihanLunas($siti, [$this->duaBulanLalu, $this->bulanLalu]);
        $this->bukti($siti, $this->tagihan($siti, $this->bulanIni), 750000, 'Bayar duluan ya pak, bulan depan mudik');

        // 4. Dodi: bukti bulan ini ditolak karena nominal kurang. KTP ditolak.
        $dodi = $this->penyewa('Dodi Kurniawan', 'KT-0004', '6281300000004', $units['A4'], $mulaiLama);
        $this->ktp($dodi, TenantIdentity::DITOLAK, 'Foto buram, NIK tidak terbaca');
        $this->tagihanLunas($dodi, [$this->duaBulanLalu, $this->bulanLalu]);
        $ditolak = $this->bukti($dodi, $this->tagihan($dodi, $this->bulanIni), 700000);
        $this->tolak($ditolak, 'Nominal kurang Rp 50.000. Mohon transfer kekurangannya lalu upload ulang.');

        // 5. Rina: tunggakan bulan lalu (telat) dan bulan ini belum bayar. Sudah diingatkan lewat WhatsApp. KTP belum ada.
        $rina = $this->penyewa('Rina Marlina', 'KT-0005', '6281300000005', $units['B1'], $mulaiLama);
        $this->tagihanLunas($rina, [$this->duaBulanLalu]);
        $this->tagihan($rina, $this->bulanLalu);
        $this->tagihan($rina, $this->bulanIni);
        $this->pada(CarbonImmutable::now()->subDay()->setTime(9, 12), fn () => Aktivitas::catat(Aksi::PengingatWhatsApp, $rina, [
            'kode_penyewa' => 'KT-0005', 'unit' => 'B1', 'periode' => [$this->bulanLalu, $this->bulanIni], 'total' => 1700000, 'status' => 'Telat',
        ], $this->dewi));

        // 6. Joko: penyewa baru masuk bulan ini, tagihan pertamanya bulan depan. KTP belum ada.
        $this->penyewa('Joko Prasetyo', 'KT-0006', '6281300000006', $units['B2'], CarbonImmutable::now()->subDays(3));

        // 7. Hendra: lunas lebih awal, KTP sah.
        $hendra = $this->penyewa('Hendra Wijaya', 'KT-0007', '6281300000007', $units['B3'], $mulaiLama);
        $this->ktp($hendra, TenantIdentity::SAH);
        $this->tagihanLunas($hendra, [$this->duaBulanLalu, $this->bulanLalu, $this->bulanIni]);

        // Laporan kerusakan dengan tahap yang berbeda-beda.
        $this->laporan($siti, 'air', 'Keran kamar mandi bocor, air menetes terus sejak kemarin.', null, CarbonImmutable::now()->subHours(3));
        $this->laporan($bambang, 'listrik', 'Stop kontak di kamar tidak ada listriknya.', StatusLaporan::Dijadwalkan, CarbonImmutable::now()->subDays(1));
        $this->laporan($hendra, 'ac', 'AC tidak dingin, keluar air dari unit dalam.', StatusLaporan::Selesai, CarbonImmutable::now()->subDays(4));
        $this->laporan($yuliani, 'furnitur', 'Engsel pintu lemari lepas.', StatusLaporan::Selesai, CarbonImmutable::now()->subDays(12), true);

        // 8. Agus: sudah keluar bulan lalu dari B4 (akun nonaktif). B4 dan C1 kosong.
        $agus = $this->penyewa('Agus Salim', 'KT-0008', '6281300000008', $units['B4'], $mulaiLama);
        $this->ktp($agus, TenantIdentity::SAH);
        $this->tagihanLunas($agus, [$this->duaBulanLalu, $this->bulanLalu]);
        $agus->leaseAktif->update(['status' => Lease::SELESAI, 'tgl_selesai' => PeriodeBayar::awalBulan($this->bulanLalu)->endOfMonth()]);
        $agus->update(['is_active' => false]);
    }

    private function penyewa(string $nama, string $kode, string $phone, Unit $unit, CarbonImmutable $mulai): User
    {
        $penyewa = User::query()->updateOrCreate(['kode_penyewa' => $kode], [
            'name' => $nama,
            'phone' => $phone,
            'password' => 'penyewa123',
            'role' => Peran::Penyewa,
            'is_active' => true,
        ]);

        $lease = Lease::query()->updateOrCreate(
            ['user_id' => $penyewa->id, 'unit_id' => $unit->id],
            ['tgl_mulai' => $mulai, 'tgl_selesai' => null, 'status' => Lease::AKTIF],
        );
        $lease->forceFill(['created_at' => $mulai])->saveQuietly();

        return $penyewa->load('leaseAktif.unit');
    }

    private function laporan(User $penyewa, string $kategori, string $deskripsi, ?StatusLaporan $status, CarbonImmutable $pada, bool $dikonfirmasi = false): void
    {
        $lease = $penyewa->leaseAktif;

        if ($lease->laporan()->where('kategori', $kategori)->exists()) {
            return;
        }

        $this->pada($pada, function () use ($lease, $penyewa, $kategori, $deskripsi): void {
            $laporan = $lease->laporan()->create([
                'kategori' => $kategori,
                'deskripsi' => $deskripsi,
                'preferensi_tanggal' => now()->addDays(2)->toDateString(),
                'preferensi_waktu' => 'pagi',
                'status' => StatusLaporan::Diterima,
            ]);
            $path = 'demo/laporan-'.$laporan->id.'.png';
            Storage::disk('local')->put($path, $this->gambar(['Foto kerusakan (contoh)', $laporan->labelKategori(), 'Unit '.$lease->unit->kode_unit], [176, 116, 21]));
            $laporan->media()->create(['file_path' => $path, 'jenis' => 'foto']);
            Aktivitas::catat(Aksi::LaporanDibuat, $laporan, ['kode_penyewa' => $penyewa->kode_penyewa, 'unit' => $lease->unit->kode_unit, 'kategori' => $laporan->labelKategori()], $penyewa);
        });

        $laporan = $lease->laporan()->where('kategori', $kategori)->first();

        if ($status === null) {
            return;
        }

        $this->pada($pada->addHours(2), function () use ($laporan, $penyewa, $lease): void {
            $laporan->update(['status' => StatusLaporan::Dijadwalkan, 'jadwal_kunjungan' => now()->addDays(2)->setTime(9, 0)]);
            Aktivitas::catat(Aksi::LaporanDijadwalkan, $laporan, ['kode_penyewa' => $penyewa->kode_penyewa, 'unit' => $lease->unit->kode_unit, 'kategori' => $laporan->labelKategori(), 'jadwal' => $laporan->jadwal_kunjungan->locale('id')->translatedFormat('l, j F').' pukul 09.00'], $this->dewi);
        });

        if ($status === StatusLaporan::Selesai) {
            $this->pada($pada->addDays(2)->addHours(4), function () use ($laporan, $penyewa, $lease, $dikonfirmasi): void {
                $laporan->update(['status' => StatusLaporan::Selesai, 'selesai_at' => now(), 'dikonfirmasi_at' => $dikonfirmasi ? now()->addHours(3) : null]);
                Aktivitas::catat(Aksi::LaporanSelesai, $laporan, ['kode_penyewa' => $penyewa->kode_penyewa, 'unit' => $lease->unit->kode_unit, 'kategori' => $laporan->labelKategori()], $this->dewi);
            });
        }
    }

    private function tagihan(User $penyewa, string $periode, StatusTagihan $status = StatusTagihan::BelumBayar): Invoice
    {
        $lease = $penyewa->leaseAktif;
        $harga = $lease->unit->harga_sewa;

        $invoice = Invoice::query()->firstOrCreate(['lease_id' => $lease->id, 'periode' => $periode], [
            'jatuh_tempo' => PeriodeBayar::jatuhTempo($periode),
            'total' => $harga,
            'status' => $status,
        ]);

        if ($invoice->items()->doesntExist()) {
            $invoice->items()->create([
                'jenis' => InvoiceItem::SEWA,
                'keterangan' => 'Sewa '.Format::bulan($periode).' unit '.$lease->unit->kode_unit,
                'nominal' => $harga,
            ]);
        }

        return $invoice;
    }

    /**
     * @param  list<string>  $periode
     */
    private function tagihanLunas(User $penyewa, array $periode): void
    {
        foreach ($periode as $p) {
            $invoice = $this->tagihan($penyewa, $p, StatusTagihan::Menunggu);

            if ($invoice->payments()->exists()) {
                continue;
            }

            $tanggal = PeriodeBayar::mulaiBayar($p)->addDays(3)->setTime(19, 30);
            $tanggal = $tanggal->greaterThan(CarbonImmutable::now()) ? CarbonImmutable::now()->subHours(2) : $tanggal;

            $payment = $this->bukti($penyewa, $invoice, $invoice->total, null, $tanggal);
            $this->pada($tanggal->addHours(14), function () use ($payment, $invoice): void {
                $penyetuju = $invoice->periode === $this->bulanIni ? $this->superAdmin : $this->dewi;
                $payment->update(['status' => Payment::DISETUJUI, 'reviewed_by' => $penyetuju->id, 'reviewed_at' => now()]);
                $invoice->update(['status' => StatusTagihan::Lunas]);
                Aktivitas::catat(Aksi::PembayaranDisetujui, $payment, $this->dataLog($payment), $penyetuju);
            });
        }
    }

    private function bukti(User $penyewa, Invoice $invoice, int $nominal, ?string $catatan = null, ?CarbonImmutable $pada = null): Payment
    {
        $pada ??= CarbonImmutable::now()->subHours(5);
        $path = 'demo/bukti-'.$penyewa->kode_penyewa.'-'.$invoice->periode.'.png';
        Storage::disk('local')->put($path, $this->gambar([
            'Transfer berhasil',
            Format::rupiah($nominal),
            'Dari: '.strtoupper($penyewa->name),
            'Ke: PANGERAN G. K.',
            'Berita: '.$penyewa->kode_penyewa.' '.$invoice->periode,
            'Tanggal: '.$pada->format('d-m-Y H:i'),
        ], [31, 138, 85]));

        return $this->pada($pada, function () use ($penyewa, $invoice, $nominal, $catatan, $path, $pada): Payment {
            $payment = $invoice->payments()->create([
                'user_id' => $penyewa->id,
                'file_path' => $path,
                'nominal_transfer' => $nominal,
                'tgl_transfer' => $pada->toDateString(),
                'catatan' => $catatan,
                'status' => Payment::MENUNGGU,
            ]);
            $invoice->update(['status' => StatusTagihan::Menunggu]);
            Aktivitas::catat(Aksi::BuktiDiupload, $payment, $this->dataLog($payment), $penyewa);

            return $payment;
        });
    }

    private function tolak(Payment $payment, string $alasan): void
    {
        $this->pada(CarbonImmutable::now()->subHours(2), function () use ($payment, $alasan): void {
            $payment->update(['status' => Payment::DITOLAK, 'alasan' => $alasan, 'reviewed_by' => $this->dewi->id, 'reviewed_at' => now()]);
            $payment->invoice->update(['status' => StatusTagihan::Ditolak]);
            Aktivitas::catat(Aksi::PembayaranDitolak, $payment, [...$this->dataLog($payment), 'alasan' => $alasan], $this->dewi);
        });
    }

    private function ktp(User $penyewa, string $status, ?string $alasan = null): void
    {
        $path = 'demo/ktp-'.$penyewa->kode_penyewa.'.png';
        Storage::disk('local')->put($path, $this->gambar([
            'KARTU TANDA PENDUDUK (CONTOH)',
            'NIK: 3201'.substr($penyewa->phone, -12),
            'Nama: '.strtoupper($penyewa->name),
        ], [44, 79, 134]));

        TenantIdentity::query()->updateOrCreate(['user_id' => $penyewa->id], [
            'nik' => '3201'.substr($penyewa->phone, -12),
            'nama_ktp' => $penyewa->name,
            'foto_path' => $path,
            'status' => $status,
            'alasan' => $alasan,
            'verified_by' => $status === TenantIdentity::MENUNGGU ? null : $this->dewi->id,
            'verified_at' => $status === TenantIdentity::MENUNGGU ? null : now(),
        ]);
    }

    /**
     * Gambar PNG sederhana berisi beberapa baris teks, sebagai pengganti foto asli.
     *
     * @param  list<string>  $baris
     * @param  array{int, int, int}  $warna
     */
    private function gambar(array $baris, array $warna): string
    {
        $gambar = imagecreatetruecolor(420, 260);
        imagefill($gambar, 0, 0, imagecolorallocate($gambar, 255, 255, 255));
        imagefilledrectangle($gambar, 0, 0, 420, 44, imagecolorallocate($gambar, ...$warna));
        imagestring($gambar, 5, 16, 14, $baris[0], imagecolorallocate($gambar, 255, 255, 255));

        $tinta = imagecolorallocate($gambar, 24, 34, 47);
        foreach (array_slice($baris, 1) as $i => $teks) {
            imagestring($gambar, 4, 16, 64 + $i * 32, $teks, $tinta);
        }

        ob_start();
        imagepng($gambar);

        return (string) ob_get_clean();
    }

    /**
     * @return array{kode_penyewa: ?string, unit: string, periode: string, nominal: int}
     */
    private function dataLog(Payment $payment): array
    {
        return [
            'kode_penyewa' => $payment->invoice->lease->penyewa->kode_penyewa,
            'unit' => $payment->invoice->lease->unit->kode_unit,
            'periode' => $payment->invoice->periode,
            'nominal' => $payment->nominal_transfer,
        ];
    }

    /**
     * Jalankan sesuatu seolah-olah pada waktu tertentu, supaya riwayat aktivitas punya jam yang wajar.
     *
     * @template T
     *
     * @param  callable(): T  $aksi
     * @return T
     */
    private function pada(CarbonImmutable $waktu, callable $aksi): mixed
    {
        $sekarang = Carbon::getTestNow();
        Carbon::setTestNow($waktu);
        CarbonImmutable::setTestNow($waktu);

        try {
            return $aksi();
        } finally {
            Carbon::setTestNow($sekarang);
            CarbonImmutable::setTestNow($sekarang);
        }
    }
}
