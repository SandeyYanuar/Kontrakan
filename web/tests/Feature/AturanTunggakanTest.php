<?php

use App\Enums\StatusTagihan;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Unit;
use App\Models\User;
use App\Services\TagihanBulan;
use App\Support\AturanTunggakan;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->lease = Lease::factory()
        ->for(User::factory()->penyewa()->state(['name' => 'Rina Marlina', 'kode_penyewa' => 'KT-0005']), 'penyewa')
        ->for(Unit::factory()->state(['kode_unit' => 'B1', 'harga_sewa' => 850000]))
        ->create(['tgl_mulai' => '2026-09-10', 'created_at' => '2026-09-10']);
    $this->oktober = Invoice::factory()->for($this->lease)->create(['periode' => '2026-10', 'jatuh_tempo' => '2026-10-05', 'total' => 850000]);
    $this->november = Invoice::factory()->for($this->lease)->create(['periode' => '2026-11', 'jatuh_tempo' => '2026-11-05', 'total' => 850000]);
    $this->admin = User::factory()->admin()->create();
});

test('satu bulan menunggak masih dalam batas, dua bulan lewat batas', function () {
    $tagihan = $this->lease->invoices;

    expect(AturanTunggakan::lewatBatas($tagihan, CarbonImmutable::parse('2026-11-05')))->toBeFalse()
        ->and(AturanTunggakan::lewatBatas($tagihan, CarbonImmutable::parse('2026-11-06')))->toBeTrue();
});

test('bukti yang sedang dicek tidak dihitung sebagai tunggakan', function () {
    $this->oktober->update(['status' => StatusTagihan::Menunggu]);

    expect(AturanTunggakan::lewatBatas($this->lease->invoices()->get(), CarbonImmutable::parse('2026-11-20')))->toBeFalse();
});

test('halaman tagihan admin menandai dan menaruh paling atas penyewa yang lewat batas', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-20 10:00'));
    $yuli = Lease::factory()->for(Unit::factory()->state(['kode_unit' => 'A2']))->create(['tgl_mulai' => '2026-09-10', 'created_at' => '2026-09-10']);
    Invoice::factory()->for($yuli)->create(['periode' => '2026-11', 'jatuh_tempo' => '2026-11-05']);

    $baris = app(TagihanBulan::class)->untuk('2026-11')['baris'];
    expect($baris->first()->lease->is($this->lease))->toBeTrue()
        ->and($baris->first()->lewatBatasTunggakan())->toBeTrue()
        ->and($baris->last()->lewatBatasTunggakan())->toBeFalse();

    $this->actingAs($this->admin)
        ->get(route('admin.tagihan', ['bulan' => '2026-11']))
        ->assertSee('Tunggakan lebih dari 1 bulan');
});

test('pesan WhatsApp untuk penyewa yang lewat batas berisi peringatan', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-20 10:00'));

    $url = $this->actingAs($this->admin)
        ->post(route('admin.tagihan.ingatkan', $this->lease), ['bulan' => '2026-11'])
        ->headers->get('Location');

    expect(rawurldecode($url))->toContain('Tunggakan sudah melewati batas 1 bulan. Mohon segera dilunasi.');
});

test('beranda penyewa yang lewat batas menampilkan peringatan', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-20 10:00'));

    $this->actingAs($this->lease->penyewa)
        ->get(route('penyewa.beranda'))
        ->assertSee('Tunggakan sudah melewati batas 1 bulan');
});

test('tagihan bulan berikutnya tidak bisa dibayar sebelum tunggakan lunas', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-20 10:00'));

    $this->actingAs($this->lease->penyewa)
        ->get(route('penyewa.beranda'))
        ->assertSee('Lunasi tagihan Oktober 2026 dulu');

    $this->actingAs($this->lease->penyewa)
        ->get(route('penyewa.bayar', $this->november))
        ->assertRedirect(route('penyewa.beranda'));

    $this->actingAs($this->lease->penyewa)
        ->post(route('penyewa.bayar.simpan', $this->november), [
            'bukti' => UploadedFile::fake()->image('b.jpg'),
            'nominal_transfer' => '850000',
            'tgl_transfer' => '2026-11-20',
        ])
        ->assertRedirect(route('penyewa.beranda'))
        ->assertSessionHas('status', 'Lunasi tagihan Oktober 2026 dulu, baru tagihan ini bisa dibayar.');

    expect(Payment::count())->toBe(0);
});

test('setelah bukti tunggakan dikirim, bulan berikutnya bisa dibayar', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-20 10:00'));
    $this->oktober->update(['status' => StatusTagihan::Menunggu]);

    $this->actingAs($this->lease->penyewa)
        ->get(route('penyewa.bayar', $this->november))
        ->assertOk();
});
