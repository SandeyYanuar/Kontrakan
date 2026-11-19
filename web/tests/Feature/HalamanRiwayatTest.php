<?php

use App\Enums\Aksi;
use App\Enums\StatusTagihan;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Unit;
use App\Models\User;
use App\Support\Aktivitas;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-19 10:21'));
    $this->superAdmin = User::factory()->superAdmin()->create(['name' => 'Pangeran']);
    $this->dewi = User::factory()->admin()->create(['name' => 'Dewi']);

    $this->siti = Lease::factory()
        ->for(User::factory()->penyewa()->state(['name' => 'Siti Aminah', 'kode_penyewa' => 'KT-0007']), 'penyewa')
        ->for(Unit::factory()->state(['kode_unit' => 'A3']))
        ->create();
    $invoice = Invoice::factory()->for($this->siti)->create(['periode' => '2026-12', 'status' => StatusTagihan::Lunas]);
    $this->payment = Payment::factory()->for($invoice)->create(['status' => Payment::DISETUJUI]);

    $this->rina = Lease::factory()
        ->for(User::factory()->penyewa()->state(['name' => 'Rina Marlina', 'kode_penyewa' => 'KT-0003']), 'penyewa')
        ->for(Unit::factory()->state(['kode_unit' => 'B1']))
        ->create();

    $this->travelTo(CarbonImmutable::parse('2026-11-18 20:05'));
    Aktivitas::catat(Aksi::PembayaranDitolak, Payment::factory()->for(Invoice::factory()->for($this->rina)->state(['periode' => '2026-11']))->create(), [
        'kode_penyewa' => 'KT-0003', 'unit' => 'B1', 'periode' => '2026-11', 'nominal' => 700000, 'alasan' => 'Nominal kurang Rp 50.000',
    ], $this->dewi);

    $this->travelTo(CarbonImmutable::parse('2026-11-19 09:12'));
    Aktivitas::catat(Aksi::PengingatWhatsApp, $this->rina->penyewa, [
        'kode_penyewa' => 'KT-0003', 'unit' => 'B1', 'periode' => ['2026-11'], 'total' => 850000, 'status' => 'Telat 4 hari',
    ], $this->dewi);

    $this->travelTo(CarbonImmutable::parse('2026-11-19 10:21'));
    Aktivitas::catat(Aksi::PembayaranDisetujui, $this->payment, [
        'kode_penyewa' => 'KT-0007', 'unit' => 'A3', 'periode' => '2026-12', 'nominal' => 750000,
    ], $this->superAdmin);
});

test('riwayat dikelompokkan per hari dengan kalimat yang mudah dibaca', function () {
    $this->actingAs($this->dewi)
        ->get(route('admin.riwayat', ['bulan' => '2026-11']))
        ->assertOk()
        ->assertSeeInOrder([
            'Hari ini, 19 November 2026',
            '10.21', 'Pangeran', 'menyetujui pembayaran A3 Siti Aminah (KT-0007) sebesar Rp 750.000 untuk Desember 2026',
            '09.12', 'Dewi', 'membuka pengingat WhatsApp untuk B1 Rina Marlina (KT-0003), total Rp 850.000 (telat 4 hari)',
            'Kemarin, 18 November 2026',
            '20.05', 'Dewi', 'menolak pembayaran B1 Rina Marlina (KT-0003) sebesar Rp 700.000 untuk November 2026',
            'Alasan: Nominal kurang Rp 50.000',
        ]);
});

test('catatan pembayaran punya tombol lihat bukti', function () {
    $this->actingAs($this->dewi)
        ->get(route('admin.riwayat', ['bulan' => '2026-11']))
        ->assertSee(route('admin.pembayaran.bukti', $this->payment), false);
});

test('filter jenis aktivitas', function () {
    $this->actingAs($this->dewi)
        ->get(route('admin.riwayat', ['bulan' => '2026-11', 'jenis' => 'pengingat']))
        ->assertSee('membuka pengingat WhatsApp')
        ->assertDontSee('menyetujui pembayaran')
        ->assertDontSee('menolak pembayaran');
});

test('filter admin', function () {
    $this->actingAs($this->dewi)
        ->get(route('admin.riwayat', ['bulan' => '2026-11', 'admin' => $this->superAdmin->id]))
        ->assertSee('menyetujui pembayaran')
        ->assertDontSee('membuka pengingat WhatsApp');
});

test('cari dengan kode penyewa, nama, atau unit', function (string $cari) {
    $this->actingAs($this->dewi)
        ->get(route('admin.riwayat', ['bulan' => '2026-11', 'cari' => $cari]))
        ->assertSee('Siti Aminah')
        ->assertDontSee('Rina Marlina');
})->with(['KT-0007', 'siti', 'A3']);

test('filter bulan', function () {
    $this->actingAs($this->dewi)
        ->get(route('admin.riwayat', ['bulan' => '2026-10']))
        ->assertSee('Belum ada aktivitas')
        ->assertDontSee('Siti Aminah');
});

test('penyewa tidak bisa membuka riwayat aktivitas', function () {
    $this->actingAs($this->siti->penyewa)
        ->get(route('admin.riwayat'))
        ->assertForbidden();
});

test('menu riwayat aktivitas mengarah ke halaman riwayat', function () {
    $this->actingAs($this->dewi)
        ->get(route('admin.tagihan'))
        ->assertSee(route('admin.riwayat'), false);
});

test('upload bukti oleh penyewa tidak menulis nama penyewa dua kali', function () {
    Aktivitas::catat(Aksi::BuktiDiupload, $this->payment, [
        'kode_penyewa' => 'KT-0007', 'unit' => 'A3', 'periode' => '2026-12', 'nominal' => 750000,
    ], $this->siti->penyewa);

    $this->actingAs($this->dewi)
        ->get(route('admin.riwayat', ['bulan' => '2026-11', 'jenis' => 'bukti']))
        ->assertSee('mengupload bukti transfer unit A3 sebesar Rp 750.000 untuk Desember 2026');
});
