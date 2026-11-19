<?php

use App\Enums\StatusTagihan;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Pengaturan;
use App\Models\Unit;
use App\Models\User;

beforeEach(function () {
    $this->lease = Lease::factory()
        ->for(User::factory()->penyewa()->state(['name' => 'Bambang Santoso', 'kode_penyewa' => 'KT-0001']), 'penyewa')
        ->for(Unit::factory()->state(['kode_unit' => 'A1']))
        ->create();
    $this->lunas = Invoice::factory()->for($this->lease)->create(['periode' => '2026-12', 'total' => 750000, 'status' => StatusTagihan::Lunas]);
    Payment::factory()->for($this->lunas)->disetujui()->create(['reviewed_by' => User::factory()->admin()->create(['name' => 'Dewi'])->id]);
});

test('penyewa mengunduh kuitansi PDF tagihannya yang lunas', function () {
    $response = $this->actingAs($this->lease->penyewa)->get(route('kuitansi', $this->lunas));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('kuitansi-KT-0001-2026-12.pdf');
    expect($response->getContent())->toStartWith('%PDF');
});

test('isi kuitansi memuat data pembayaran', function () {
    $this->actingAs($this->lease->penyewa);
    $html = view('kuitansi', [
        'invoice' => $this->lunas->load('lease.unit'),
        'penyewa' => $this->lease->penyewa,
        'pembayaran' => $this->lunas->payments()->with('pemeriksa')->first(),
        'nomor' => 'KW/202612/KT-0001',
        'pengaturan' => Pengaturan::semua(),
    ])->render();

    expect($html)->toContain('Bambang Santoso')
        ->toContain('KT-0001')
        ->toContain('Sewa Desember 2026, unit A1')
        ->toContain('Rp 750.000')
        ->toContain('Tujuh ratus lima puluh ribu rupiah')
        ->toContain('Disetujui oleh Dewi');
});

test('kuitansi hanya untuk tagihan yang lunas', function () {
    $belum = Invoice::factory()->for($this->lease)->create(['periode' => '2027-01']);

    $this->actingAs($this->lease->penyewa)->get(route('kuitansi', $belum))->assertNotFound();
});

test('penyewa lain tidak bisa mengunduh kuitansi', function () {
    $this->actingAs(Lease::factory()->create()->penyewa)->get(route('kuitansi', $this->lunas))->assertNotFound();
});

test('admin bisa mengunduh kuitansi penyewa', function () {
    $this->actingAs(User::factory()->admin()->create())->get(route('kuitansi', $this->lunas))->assertOk();
});

test('riwayat bayar penyewa punya tombol kuitansi', function () {
    $this->actingAs($this->lease->penyewa)
        ->get(route('penyewa.riwayat'))
        ->assertSee(route('kuitansi', $this->lunas), false);
});
