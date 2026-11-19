<?php

use App\Enums\Aksi;
use App\Enums\StatusTagihan;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Pengaturan;
use App\Models\Unit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->travelTo(CarbonImmutable::parse('2026-12-02 10:00'));
    $this->lease = Lease::factory()
        ->for(User::factory()->penyewa()->state(['name' => 'Dodi Kurniawan', 'kode_penyewa' => 'KT-0011']), 'penyewa')
        ->for(Unit::factory()->state(['kode_unit' => 'A4', 'harga_sewa' => 750000]))
        ->create(['tgl_mulai' => '2026-11-10', 'created_at' => '2026-11-10']);
    $this->penyewa = $this->lease->penyewa;
    Pengaturan::simpan(['bank_nama' => 'Bank Contoh', 'bank_nomor' => '1234 5678 4821', 'bank_atas_nama' => 'Pangeran G. K.', 'whatsapp_admin' => '6281200000001']);
});

function tagihanPenyewa(Lease $lease, string $periode, StatusTagihan $status = StatusTagihan::BelumBayar): Invoice
{
    return Invoice::factory()->for($lease)->create([
        'periode' => $periode,
        'jatuh_tempo' => $periode.'-05',
        'total' => 750000,
        'status' => $status,
    ]);
}

function dataBukti(array $ubah = []): array
{
    return array_merge([
        'bukti' => UploadedFile::fake()->image('bukti.jpg')->size(300),
        'nominal_transfer' => 'Rp 750.000',
        'tgl_transfer' => '2026-12-02',
        'nama_pengirim' => '',
        'catatan' => 'Bayar Desember',
    ], $ubah);
}

test('beranda menampilkan tagihan yang belum dibayar, rekening tujuan, dan kode untuk berita transfer', function () {
    tagihanPenyewa($this->lease, '2026-12');

    $this->actingAs($this->penyewa)
        ->get(route('penyewa.beranda'))
        ->assertOk()
        ->assertSee(['Dodi Kurniawan', 'KT-0011', 'Unit A4'])
        ->assertSee('Tagihan Desember 2026')
        ->assertSee('Rp 750.000')
        ->assertSee('Jatuh tempo 5 Desember, 3 hari lagi')
        ->assertSee('Upload bukti transfer')
        ->assertSee('1234 5678 4821')
        ->assertSee('Bank Contoh, a.n. Pangeran G. K.')
        ->assertSee('https://wa.me/6281200000001', false);
});

test('beranda menampilkan tunggakan lebih dulu dan setiap keadaan tagihan', function () {
    tagihanPenyewa($this->lease, '2026-11');
    $menunggu = tagihanPenyewa($this->lease, '2026-12', StatusTagihan::Menunggu);
    Payment::factory()->for($menunggu)->create(['created_at' => '2026-12-01 19:45']);

    $this->actingAs($this->penyewa)
        ->get(route('penyewa.beranda'))
        ->assertSeeInOrder(['Tagihan November 2026', 'Telat 27 hari', 'Menunggu dicek pengelola', 'Tagihan Desember 2026', 'Bukti dikirim 1 Des']);
});

test('tagihan yang ditolak menampilkan alasan dan tombol upload ulang', function () {
    $invoice = tagihanPenyewa($this->lease, '2026-12', StatusTagihan::Ditolak);
    Payment::factory()->for($invoice)->ditolak('Nominal kurang Rp 50.000')->create();

    $this->actingAs($this->penyewa)
        ->get(route('penyewa.beranda'))
        ->assertSee('Bukti transfer ditolak')
        ->assertSee('Nominal kurang Rp 50.000')
        ->assertSee('Upload ulang');
});

test('kalau semua lunas, beranda menampilkan kartu lunas tanpa rekening', function () {
    tagihanPenyewa($this->lease, '2026-12', StatusTagihan::Lunas);

    $this->actingAs($this->penyewa)
        ->get(route('penyewa.beranda'))
        ->assertSee('Tagihan Desember 2026 lunas')
        ->assertDontSee('1234 5678 4821');
});

test('penyewa mengupload bukti transfer: tagihan jadi menunggu dicek dan tercatat', function () {
    $invoice = tagihanPenyewa($this->lease, '2026-12');

    $this->actingAs($this->penyewa)->get(route('penyewa.bayar', $invoice))->assertOk()->assertSee('Rp 750.000');

    $this->actingAs($this->penyewa)
        ->post(route('penyewa.bayar.simpan', $invoice), dataBukti())
        ->assertRedirect(route('penyewa.beranda'))
        ->assertSessionHas('status', 'Bukti transfer terkirim. Pengelola akan mengecek rekening dulu.');

    $payment = Payment::sole();
    expect($invoice->fresh()->status)->toBe(StatusTagihan::Menunggu)
        ->and($payment->status)->toBe(Payment::MENUNGGU)
        ->and($payment->nominal_transfer)->toBe(750000)
        ->and($payment->user_id)->toBe($this->penyewa->id)
        ->and($payment->file_path)->toStartWith('bukti/');
    Storage::disk('local')->assertExists($payment->file_path);

    $log = ActivityLog::sole();
    expect($log->aksi)->toBe(Aksi::BuktiDiupload)
        ->and($log->actor_id)->toBe($this->penyewa->id)
        ->and($log->data)->toMatchArray(['kode_penyewa' => 'KT-0011', 'periode' => '2026-12', 'nominal' => 750000]);
});

test('bukti boleh berupa PDF', function () {
    $invoice = tagihanPenyewa($this->lease, '2026-12');

    $this->actingAs($this->penyewa)
        ->post(route('penyewa.bayar.simpan', $invoice), dataBukti(['bukti' => UploadedFile::fake()->create('bukti.pdf', 200, 'application/pdf')]))
        ->assertSessionHasNoErrors();
});

test('isian bukti transfer divalidasi', function (array $ubah, string $kolom) {
    $invoice = tagihanPenyewa($this->lease, '2026-12');

    $this->actingAs($this->penyewa)
        ->from(route('penyewa.bayar', $invoice))
        ->post(route('penyewa.bayar.simpan', $invoice), dataBukti($ubah))
        ->assertSessionHasErrors($kolom);

    expect(Payment::count())->toBe(0);
})->with([
    'tanpa file' => [['bukti' => null], 'bukti'],
    'file lebih dari 2 MB' => [['bukti' => UploadedFile::fake()->image('b.jpg')->size(2100)], 'bukti'],
    'file bukan gambar atau PDF' => [['bukti' => UploadedFile::fake()->create('b.docx', 10)], 'bukti'],
    'nominal kosong' => [['nominal_transfer' => ''], 'nominal_transfer'],
    'tanggal di masa depan' => [['tgl_transfer' => '2026-12-10'], 'tgl_transfer'],
]);

test('penyewa tidak bisa membayar tagihan milik orang lain', function () {
    $lain = tagihanPenyewa(Lease::factory()->create(), '2026-12');

    $this->actingAs($this->penyewa)->get(route('penyewa.bayar', $lain))->assertNotFound();
    $this->actingAs($this->penyewa)->post(route('penyewa.bayar.simpan', $lain), dataBukti())->assertNotFound();
});

test('tagihan yang lunas atau sedang dicek tidak bisa diupload lagi', function (StatusTagihan $status) {
    $invoice = tagihanPenyewa($this->lease, '2026-12', $status);

    $this->actingAs($this->penyewa)
        ->post(route('penyewa.bayar.simpan', $invoice), dataBukti())
        ->assertRedirect(route('penyewa.beranda'));

    expect(Payment::count())->toBe(0);
})->with([StatusTagihan::Lunas, StatusTagihan::Menunggu]);

test('tagihan yang ditolak bisa diupload ulang', function () {
    $invoice = tagihanPenyewa($this->lease, '2026-12', StatusTagihan::Ditolak);
    Payment::factory()->for($invoice)->ditolak()->create();

    $this->actingAs($this->penyewa)->post(route('penyewa.bayar.simpan', $invoice), dataBukti());

    expect($invoice->fresh()->status)->toBe(StatusTagihan::Menunggu)
        ->and($invoice->payments()->count())->toBe(2);
});

test('penyewa melihat bukti transfer miliknya sendiri saja', function () {
    $invoice = tagihanPenyewa($this->lease, '2026-12');
    $this->actingAs($this->penyewa)->post(route('penyewa.bayar.simpan', $invoice), dataBukti());
    $payment = Payment::sole();

    $this->actingAs($this->penyewa)->get(route('penyewa.bukti', $payment))->assertOk();
    $this->actingAs(Lease::factory()->create()->penyewa)->get(route('penyewa.bukti', $payment))->assertNotFound();
});

test('riwayat bayar menampilkan tagihan yang sudah lunas', function () {
    tagihanPenyewa($this->lease, '2026-11', StatusTagihan::Lunas);
    tagihanPenyewa($this->lease, '2026-12');

    $this->actingAs($this->penyewa)
        ->get(route('penyewa.riwayat'))
        ->assertOk()
        ->assertSee('November 2026')
        ->assertDontSee('Desember 2026');
});

test('profil: penyewa mengubah nomor darurat dan bisa keluar', function () {
    $this->actingAs($this->penyewa)
        ->get(route('penyewa.profil'))
        ->assertOk()
        ->assertSee(['Data KTP', 'Ganti password', 'Keluar']);

    $this->actingAs($this->penyewa)
        ->put(route('penyewa.profil.darurat'), ['phone_darurat' => '0812 0000 9999'])
        ->assertRedirect(route('penyewa.profil'));

    expect($this->penyewa->fresh()->phone_darurat)->toBe('6281200009999')
        ->and(ActivityLog::sole()->aksi)->toBe(Aksi::NomorDaruratDiubah);
});
