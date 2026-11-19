<?php

use App\Enums\Aksi;
use App\Enums\StatusTagihan;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Unit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->travelTo(CarbonImmutable::parse('2026-12-02 10:00'));
    $this->lease = Lease::factory()
        ->for(User::factory()->penyewa()->state(['name' => 'Siti Aminah', 'kode_penyewa' => 'KT-0007']), 'penyewa')
        ->for(Unit::factory()->state(['kode_unit' => 'A3', 'harga_sewa' => 750000]))
        ->create(['tgl_mulai' => '2026-11-10', 'created_at' => '2026-11-10']);
    $this->invoice = Invoice::factory()->for($this->lease)->create([
        'periode' => '2026-12', 'jatuh_tempo' => '2026-12-05', 'total' => 750000, 'status' => StatusTagihan::Menunggu,
    ]);
    Storage::disk('local')->put('bukti/1/struk.jpg', UploadedFile::fake()->image('struk.jpg')->getContent());
    $this->payment = Payment::factory()->for($this->invoice)->create([
        'file_path' => 'bukti/1/struk.jpg',
        'nominal_transfer' => 700000,
        'tgl_transfer' => '2026-12-01',
        'nama_pengirim' => 'Ahmad',
        'catatan' => 'Bayar duluan ya pak',
    ]);
    $this->admin = User::factory()->admin()->create();
});

test('halaman tagihan memuat pop-up bukti dengan nominal yang dibandingkan', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.tagihan', ['bulan' => '2026-12']))
        ->assertOk()
        ->assertSee('Bukti transfer unit A3')
        ->assertSee(['Tagihan Desember 2026', 'Rp 750.000', 'Rp 700.000'])
        ->assertSee('Kurang Rp 50.000')
        ->assertSee('Bayar duluan ya pak')
        ->assertSee(['Setujui pembayaran', 'Tolak', 'Batal']);
});

test('admin membuka gambar bukti transfer', function () {
    $this->actingAs($this->admin)->get(route('admin.pembayaran.bukti', $this->payment))->assertOk();
});

test('penyewa tidak bisa membuka bukti lewat rute admin', function () {
    $this->actingAs($this->lease->penyewa)->get(route('admin.pembayaran.bukti', $this->payment))->assertForbidden();
});

test('setujui: tagihan lunas, bukti disetujui, tercatat', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.tagihan'))
        ->post(route('admin.pembayaran.setujui', $this->payment))
        ->assertRedirect(route('admin.tagihan'))
        ->assertSessionHas('status', 'Pembayaran A3 Desember 2026 disetujui. Status jadi lunas.');

    expect($this->invoice->fresh()->status)->toBe(StatusTagihan::Lunas)
        ->and($this->payment->fresh())
        ->status->toBe(Payment::DISETUJUI)
        ->reviewed_by->toBe($this->admin->id);

    $log = ActivityLog::sole();
    expect($log->aksi)->toBe(Aksi::PembayaranDisetujui)
        ->and($log->actor_id)->toBe($this->admin->id)
        ->and($log->subject->is($this->payment))->toBeTrue()
        ->and($log->data)->toMatchArray(['kode_penyewa' => 'KT-0007', 'unit' => 'A3', 'periode' => '2026-12', 'nominal' => 700000]);
});

test('tolak wajib dengan alasan', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.tagihan'))
        ->post(route('admin.pembayaran.tolak', $this->payment), ['alasan' => ''])
        ->assertSessionHasErrors(['alasan' => 'Tulis alasan penolakan supaya penyewa tahu apa yang harus diperbaiki.'])
        ->assertSessionHasInput('payment_id', $this->payment->id);

    expect($this->invoice->fresh()->status)->toBe(StatusTagihan::Menunggu);
});

test('tolak: tagihan ditolak, alasan tampil di beranda penyewa, tercatat', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.pembayaran.tolak', $this->payment), ['alasan' => 'Nominal kurang Rp 50.000'])
        ->assertSessionHas('status', 'Pembayaran A3 Desember 2026 ditolak. Penyewa melihat alasannya di berandanya.');

    expect($this->invoice->fresh()->status)->toBe(StatusTagihan::Ditolak)
        ->and($this->payment->fresh()->status)->toBe(Payment::DITOLAK)
        ->and(ActivityLog::sole()->data['alasan'])->toBe('Nominal kurang Rp 50.000');

    $this->actingAs($this->lease->penyewa)
        ->get(route('penyewa.beranda'))
        ->assertSee('Nominal kurang Rp 50.000')
        ->assertSee('Upload ulang');
});

test('bukti yang sudah dicek tidak bisa disetujui atau ditolak lagi', function () {
    $this->actingAs($this->admin)->post(route('admin.pembayaran.setujui', $this->payment));

    $this->actingAs($this->admin)
        ->from(route('admin.tagihan'))
        ->post(route('admin.pembayaran.tolak', $this->payment), ['alasan' => 'X'])
        ->assertSessionHas('status', 'Bukti ini sudah dicek sebelumnya.');

    expect($this->invoice->fresh()->status)->toBe(StatusTagihan::Lunas)
        ->and(ActivityLog::count())->toBe(1);
});

test('penyewa tidak bisa menyetujui pembayarannya sendiri', function () {
    $this->actingAs($this->lease->penyewa)
        ->post(route('admin.pembayaran.setujui', $this->payment))
        ->assertForbidden();
});

test('super admin membatalkan persetujuan yang salah: tagihan kembali belum bayar', function () {
    $this->actingAs($this->admin)->post(route('admin.pembayaran.setujui', $this->payment));
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)
        ->from(route('admin.penyewa.show', $this->lease->penyewa))
        ->post(route('admin.pembayaran.batalkan', $this->payment), ['alasan' => 'Uang belum masuk rekening'])
        ->assertRedirect(route('admin.penyewa.show', $this->lease->penyewa));

    expect($this->invoice->fresh()->status)->toBe(StatusTagihan::BelumBayar)
        ->and($this->payment->fresh()->status)->toBe(Payment::DIBATALKAN)
        ->and(ActivityLog::orderBy('id')->pluck('aksi')->all())->toBe([Aksi::PembayaranDisetujui, Aksi::PersetujuanDibatalkan])
        ->and(ActivityLog::latest('id')->first()->data['alasan'])->toBe('Uang belum masuk rekening');
});

test('admin biasa tidak bisa membatalkan persetujuan', function () {
    $this->actingAs($this->admin)->post(route('admin.pembayaran.setujui', $this->payment));

    $this->actingAs($this->admin)
        ->post(route('admin.pembayaran.batalkan', $this->payment), ['alasan' => 'X'])
        ->assertForbidden();

    expect($this->invoice->fresh()->status)->toBe(StatusTagihan::Lunas);
});

test('detail penyewa menampilkan riwayat tagihan dan tombol batalkan untuk super admin', function () {
    $this->actingAs($this->admin)->post(route('admin.pembayaran.setujui', $this->payment));

    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('admin.penyewa.show', $this->lease->penyewa))
        ->assertSee('Desember 2026')
        ->assertSee('Batalkan persetujuan');

    $this->actingAs($this->admin)
        ->get(route('admin.penyewa.show', $this->lease->penyewa))
        ->assertDontSee('Batalkan persetujuan');
});
