<?php

use App\Enums\Aksi;
use App\Enums\StatusLaporan;
use App\Models\ActivityLog;
use App\Models\Lease;
use App\Models\MaintenanceTicket;
use App\Models\Unit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->travelTo(CarbonImmutable::parse('2026-11-20 10:00'));
    $this->lease = Lease::factory()
        ->for(User::factory()->penyewa()->state(['name' => 'Siti Aminah', 'kode_penyewa' => 'KT-0003', 'phone' => '6281300000003']), 'penyewa')
        ->for(Unit::factory()->state(['kode_unit' => 'A3']))
        ->create();
    $this->penyewa = $this->lease->penyewa;
    $this->admin = User::factory()->admin()->create(['name' => 'Dewi']);
});

function dataLaporan(array $ubah = []): array
{
    return array_merge([
        'kategori' => 'air',
        'deskripsi' => 'Keran kamar mandi bocor, air menetes terus.',
        'foto' => [UploadedFile::fake()->image('keran.jpg')->size(800)],
        'preferensi_tanggal' => '2026-11-22',
        'preferensi_waktu' => 'pagi',
    ], $ubah);
}

test('beranda penyewa punya tombol lapor kerusakan', function () {
    $this->actingAs($this->penyewa)
        ->get(route('penyewa.beranda'))
        ->assertSee(route('penyewa.laporan.create'), false)
        ->assertSee('Lapor kerusakan');
});

test('penyewa mengirim laporan kerusakan dengan foto', function () {
    $this->actingAs($this->penyewa)->get(route('penyewa.laporan.create'))
        ->assertOk()
        ->assertSeeInOrder(['Listrik', 'Air atau pipa', 'Tembok', 'Atap', 'Pintu', 'Lainnya'])
        ->assertDontSee('Furnitur');

    $this->actingAs($this->penyewa)
        ->post(route('penyewa.laporan.store'), dataLaporan())
        ->assertRedirect(route('penyewa.laporan.index'))
        ->assertSessionHas('status', 'Laporan terkirim. Pengelola akan menjadwalkan perbaikan.');

    $laporan = MaintenanceTicket::sole();
    expect($laporan->status)->toBe(StatusLaporan::Diterima)
        ->and($laporan->lease->is($this->lease))->toBeTrue()
        ->and($laporan->labelKategori())->toBe('Air atau pipa')
        ->and($laporan->labelPreferensi())->toBe('Minggu, 22 November, pagi')
        ->and($laporan->media)->toHaveCount(1);
    Storage::disk('local')->assertExists($laporan->media->first()->file_path);

    $log = ActivityLog::sole();
    expect($log->aksi)->toBe(Aksi::LaporanDibuat)->and($log->actor_id)->toBe($this->penyewa->id);
});

test('isian laporan divalidasi', function (array $ubah, string $kolom) {
    $this->actingAs($this->penyewa)
        ->from(route('penyewa.laporan.create'))
        ->post(route('penyewa.laporan.store'), dataLaporan($ubah))
        ->assertSessionHasErrors($kolom);
})->with([
    'kategori tidak dikenal' => [['kategori' => 'sihir'], 'kategori'],
    'deskripsi kosong' => [['deskripsi' => ''], 'deskripsi'],
    'lebih dari 3 foto' => [['foto' => array_fill(0, 4, UploadedFile::fake()->image('a.jpg'))], 'foto'],
    'foto bukan gambar' => [['foto' => [UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')]], 'foto.0'],
    'foto lebih dari 2 MB' => [['foto' => [UploadedFile::fake()->image('a.jpg')->size(2100)]], 'foto.0'],
    'tanggal kunjungan sudah lewat' => [['preferensi_tanggal' => '2026-11-19'], 'preferensi_tanggal'],
]);

test('foto boleh dikosongkan', function () {
    $this->actingAs($this->penyewa)
        ->post(route('penyewa.laporan.store'), dataLaporan(['foto' => []]))
        ->assertSessionHasNoErrors();
});

test('penyewa melihat laporannya dengan tahapan status', function () {
    $laporan = MaintenanceTicket::factory()->for($this->lease)->create(['status' => StatusLaporan::Dijadwalkan, 'jadwal_kunjungan' => '2026-11-22 09:00']);

    $this->actingAs($this->penyewa)
        ->get(route('penyewa.laporan.index'))
        ->assertSeeInOrder(['Laporan diterima', 'Teknisi dijadwalkan', 'Sedang dikerjakan', 'Selesai'])
        ->assertSee('Minggu, 22 November pukul 09.00');
});

test('penyewa lain tidak bisa melihat foto laporan', function () {
    $this->actingAs($this->penyewa)->post(route('penyewa.laporan.store'), dataLaporan());
    $media = MaintenanceTicket::sole()->media->first();

    $this->actingAs($this->penyewa)->get(route('laporan.foto', $media))->assertOk();
    $this->actingAs($this->admin)->get(route('laporan.foto', $media))->assertOk();
    $this->actingAs(Lease::factory()->create()->penyewa)->get(route('laporan.foto', $media))->assertNotFound();
});

test('admin melihat daftar laporan, yang aktif di atas', function () {
    MaintenanceTicket::factory()->for($this->lease)->create(['kategori' => 'atap', 'status' => StatusLaporan::Selesai, 'dikonfirmasi_at' => now()]);
    MaintenanceTicket::factory()->for($this->lease)->create(['kategori' => 'listrik']);

    $this->actingAs($this->admin)
        ->get(route('admin.laporan.index'))
        ->assertOk()
        ->assertSeeInOrder(['Perlu ditangani (1)', 'Listrik', 'Sudah beres (1)', 'Atap']);
});

test('admin menjadwalkan, mengerjakan, lalu menyelesaikan perbaikan', function () {
    $laporan = MaintenanceTicket::factory()->for($this->lease)->create();

    $this->actingAs($this->admin)->get(route('admin.laporan.show', $laporan))->assertOk()->assertSee('Keran kamar mandi bocor');

    $this->actingAs($this->admin)
        ->post(route('admin.laporan.jadwal', $laporan), ['tanggal' => '2026-11-22', 'jam' => '09:00'])
        ->assertRedirect(route('admin.laporan.show', $laporan));
    expect($laporan->fresh())
        ->status->toBe(StatusLaporan::Dijadwalkan)
        ->jadwal_kunjungan->format('Y-m-d H:i')->toBe('2026-11-22 09:00');

    $this->actingAs($this->admin)->post(route('admin.laporan.status', $laporan), ['status' => 'dikerjakan']);
    expect($laporan->fresh()->status)->toBe(StatusLaporan::Dikerjakan);

    $this->actingAs($this->admin)->post(route('admin.laporan.status', $laporan), ['status' => 'selesai']);
    expect($laporan->fresh()->status)->toBe(StatusLaporan::Selesai)
        ->and($laporan->fresh()->selesai_at)->not->toBeNull();

    expect(ActivityLog::orderBy('id')->pluck('aksi')->all())
        ->toBe([Aksi::LaporanDijadwalkan, Aksi::LaporanDikerjakan, Aksi::LaporanSelesai]);
});

test('detail laporan admin punya tombol WhatsApp ke penyewa', function () {
    $laporan = MaintenanceTicket::factory()->for($this->lease)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.laporan.show', $laporan))
        ->assertSee('https://wa.me/6281300000003', false);
});

test('penyewa mengonfirmasi perbaikan sudah beres setelah selesai', function () {
    $laporan = MaintenanceTicket::factory()->for($this->lease)->create(['status' => StatusLaporan::Dikerjakan]);

    $this->actingAs($this->penyewa)->post(route('penyewa.laporan.konfirmasi', $laporan))->assertRedirect();
    expect($laporan->fresh()->dikonfirmasi_at)->toBeNull();

    $laporan->update(['status' => StatusLaporan::Selesai]);
    $this->actingAs($this->penyewa)
        ->post(route('penyewa.laporan.konfirmasi', $laporan))
        ->assertRedirect(route('penyewa.laporan.index'));

    expect($laporan->fresh()->dikonfirmasi_at)->not->toBeNull()
        ->and(ActivityLog::sole()->aksi)->toBe(Aksi::LaporanDikonfirmasi);
});

test('penyewa tidak bisa mengonfirmasi laporan orang lain atau membuka halaman admin laporan', function () {
    $lain = MaintenanceTicket::factory()->create(['status' => StatusLaporan::Selesai]);

    $this->actingAs($this->penyewa)->post(route('penyewa.laporan.konfirmasi', $lain))->assertNotFound();
    $this->actingAs($this->penyewa)->get(route('admin.laporan.index'))->assertForbidden();
});
