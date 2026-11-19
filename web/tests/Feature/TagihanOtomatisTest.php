<?php

use App\Enums\Aksi;
use App\Enums\StatusTagihan;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Pengaturan;
use App\Models\Unit;
use App\Models\User;
use App\Services\PembuatTagihan;
use App\Support\PeriodeBayar;
use Carbon\CarbonImmutable;

function kontrak(string $tglMulai, string $dicatat, int $harga = 750000): Lease
{
    return Lease::factory()
        ->for(Unit::factory()->state(['kode_unit' => 'A1', 'harga_sewa' => $harga]))
        ->create(['tgl_mulai' => $tglMulai, 'created_at' => $dicatat]);
}

function buatPada(string $tanggal): int
{
    return app(PembuatTagihan::class)->buatOtomatis(CarbonImmutable::parse($tanggal));
}

test('periode bayar bawaan: 27 bulan sebelumnya sampai 5, tagihan dibuat 14 hari sebelum jatuh tempo', function () {
    expect(PeriodeBayar::jatuhTempo('2026-12')->toDateString())->toBe('2026-12-05')
        ->and(PeriodeBayar::mulaiBayar('2026-12')->toDateString())->toBe('2026-11-27')
        ->and(PeriodeBayar::tanggalDibuat('2026-12')->toDateString())->toBe('2026-11-21')
        ->and(PeriodeBayar::mulaiBayar('2027-03')->toDateString())->toBe('2027-02-27');
});

test('periode bayar dalam bulan yang sama kalau tanggal mulai lebih kecil', function () {
    Pengaturan::simpan(['tanggal_mulai_bayar' => '1', 'tanggal_jatuh_tempo' => '5']);

    expect(PeriodeBayar::mulaiBayar('2026-12')->toDateString())->toBe('2026-12-01');
});

test('tagihan dibuat tepat 14 hari sebelum jatuh tempo, tidak sehari sebelumnya', function () {
    kontrak('2026-11-10', '2026-11-10');

    expect(buatPada('2026-11-20'))->toBe(0)
        ->and(buatPada('2026-11-21'))->toBe(1);

    $invoice = Invoice::sole();
    expect($invoice->periode)->toBe('2026-12')
        ->and($invoice->jatuh_tempo->toDateString())->toBe('2026-12-05')
        ->and($invoice->total)->toBe(750000)
        ->and($invoice->status)->toBe(StatusTagihan::BelumBayar)
        ->and($invoice->items()->sole()->keterangan)->toBe('Sewa Desember 2026 unit A1');
});

test('tagihan pertama adalah bulan setelah bulan masuk', function () {
    kontrak('2026-12-10', '2026-12-10');

    buatPada('2027-03-01');

    expect(Invoice::orderBy('periode')->pluck('periode')->all())->toBe(['2027-01', '2027-02', '2027-03']);
});

test('penyewa lama yang baru dicatat tidak ditagih untuk bulan yang sudah lewat jatuh tempo', function () {
    kontrak('2024-03-01', '2026-11-20');

    buatPada('2026-11-21');

    expect(Invoice::pluck('periode')->all())->toBe(['2026-12']);
});

test('dijalankan berulang tidak membuat tagihan ganda', function () {
    kontrak('2026-10-10', '2026-10-10');

    buatPada('2026-11-25');
    buatPada('2026-11-25');
    buatPada('2026-11-26');

    expect(Invoice::count())->toBe(2);
});

test('tunggakan dibuat juga kalau aplikasi lama tidak dibuka', function () {
    kontrak('2026-10-10', '2026-10-10');

    expect(buatPada('2027-02-01'))->toBe(4);
    expect(Invoice::orderBy('periode')->pluck('periode')->all())->toBe(['2026-11', '2026-12', '2027-01', '2027-02']);
});

test('kontrak yang sudah selesai tidak ditagih lagi', function () {
    Lease::factory()->selesai()->create(['tgl_mulai' => '2026-01-01', 'created_at' => '2026-01-01']);

    expect(buatPada('2026-12-01'))->toBe(0);
});

test('perubahan harga hanya berlaku untuk tagihan yang belum dibuat', function () {
    $lease = kontrak('2026-10-10', '2026-10-10');
    buatPada('2026-11-21');

    $lease->unit->update(['harga_sewa' => 800000]);
    buatPada('2026-12-22');

    expect(Invoice::orderBy('periode')->pluck('total', 'periode')->all())
        ->toBe(['2026-11' => 750000, '2026-12' => 750000, '2027-01' => 800000]);
});

test('jatuh tempo Februari tetap tanggal 5', function () {
    kontrak('2026-12-10', '2026-12-10');
    buatPada('2027-01-22');

    expect(Invoice::where('periode', '2027-02')->sole()->jatuh_tempo->toDateString())->toBe('2027-02-05');
});

test('label waktu mengikuti periode bayar 27 sampai 5', function (string $hariIni, string $label) {
    $invoice = Invoice::factory()->create(['periode' => '2026-12', 'jatuh_tempo' => '2026-12-05']);

    expect($invoice->label(CarbonImmutable::parse($hariIni)))->toBe($label);
})->with([
    '21 Nov, tagihan baru muncul' => ['2026-11-21', Invoice::LABEL_BELUM_PERIODE],
    '26 Nov, sehari sebelum periode' => ['2026-11-26', Invoice::LABEL_BELUM_PERIODE],
    '27 Nov, periode mulai' => ['2026-11-27', Invoice::LABEL_DEKAT],
    '5 Des, jatuh tempo' => ['2026-12-05', Invoice::LABEL_DEKAT],
    '6 Des, telat' => ['2026-12-06', Invoice::LABEL_TELAT],
]);

test('status yang disimpan mengalahkan label waktu', function () {
    $hariTelat = CarbonImmutable::parse('2026-12-20');

    expect(Invoice::factory()->lunas()->create()->label($hariTelat))->toBe(Invoice::LABEL_LUNAS)
        ->and(Invoice::factory()->menunggu()->create(['periode' => '2027-01'])->label($hariTelat))->toBe(Invoice::LABEL_MENUNGGU)
        ->and(Invoice::factory()->ditolak()->create(['periode' => '2027-02'])->label($hariTelat))->toBe(Invoice::LABEL_DITOLAK);
});

test('jumlah hari telat dihitung dari jatuh tempo', function () {
    $invoice = Invoice::factory()->create(['jatuh_tempo' => '2026-12-05']);

    expect($invoice->hariTelat(CarbonImmutable::parse('2026-12-05')))->toBe(0)
        ->and($invoice->hariTelat(CarbonImmutable::parse('2026-12-09')))->toBe(4);
});

test('command tagihan:buat bisa dijalankan dengan tanggal tertentu', function () {
    kontrak('2026-10-10', '2026-10-10');

    $this->artisan('tagihan:buat', ['--tanggal' => '2026-11-21'])
        ->expectsOutput('2 tagihan baru dibuat.')
        ->assertSuccessful();
});

test('tagihan bulan berikutnya bisa dibuat lebih awal secara manual', function () {
    $lease = kontrak('2026-10-10', '2026-10-10');
    buatPada('2026-11-21');

    $invoice = app(PembuatTagihan::class)->buatBulanBerikutnya($lease);

    expect($invoice->periode)->toBe('2027-01')
        ->and(Invoice::count())->toBe(3);

    buatPada('2026-12-22');
    expect(Invoice::count())->toBe(3);
});

test('membuka halaman tagihan admin ikut membuat tagihan yang sudah waktunya', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-21 09:00'));
    kontrak('2026-11-10', '2026-11-10');

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.tagihan', ['bulan' => '2026-12']))
        ->assertOk()
        ->assertSee('A1');

    expect(Invoice::pluck('periode')->all())->toBe(['2026-12']);
});

test('admin membuat tagihan bulan berikutnya lebih awal dan tercatat di riwayat', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-21 09:00'));
    $lease = kontrak('2026-11-10', '2026-11-10');
    app(PembuatTagihan::class)->buatOtomatis();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('admin.tagihan'))
        ->post(route('admin.tagihan.bulan-berikutnya', $lease))
        ->assertRedirect(route('admin.tagihan'))
        ->assertSessionHas('status');

    expect(Invoice::orderBy('periode')->pluck('periode')->all())->toBe(['2026-12', '2027-01']);

    $log = ActivityLog::sole();
    expect($log->aksi)->toBe(Aksi::TagihanDibuatManual)
        ->and($log->actor_id)->toBe($admin->id)
        ->and($log->data['periode'])->toBe('2027-01');
});

test('penyewa tidak bisa membuat tagihan manual', function () {
    $lease = kontrak('2026-11-10', '2026-11-10');

    $this->actingAs($lease->penyewa)
        ->post(route('admin.tagihan.bulan-berikutnya', $lease))
        ->assertForbidden();
});
