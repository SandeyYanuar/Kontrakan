<?php

use App\Enums\Aksi;
use App\Models\ActivityLog;
use App\Models\Lease;
use App\Models\Pengumuman;
use App\Models\User;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-20 10:00'));
    $this->admin = User::factory()->admin()->create(['name' => 'Dewi']);
    $this->penyewa = Lease::factory()->create()->penyewa;
});

test('admin menerbitkan pengumuman dan tercatat', function () {
    $this->actingAs($this->admin)->get(route('admin.pengumuman.create'))->assertOk();

    $this->actingAs($this->admin)
        ->post(route('admin.pengumuman.store'), [
            'judul' => 'Fogging Sabtu pagi',
            'isi' => 'Akan ada fogging nyamuk Sabtu pukul 07.00. Mohon tutup makanan.',
            'tampil_mulai' => '2026-11-20',
            'tampil_sampai' => '2026-11-23',
        ])
        ->assertRedirect(route('admin.pengumuman.index'))
        ->assertSessionHas('status', 'Pengumuman diterbitkan.');

    $pengumuman = Pengumuman::sole();
    expect($pengumuman->created_by)->toBe($this->admin->id)
        ->and($pengumuman->keadaan())->toBe('tampil');

    $log = ActivityLog::sole();
    expect($log->aksi)->toBe(Aksi::PengumumanDibuat)->and($log->data['judul'])->toBe('Fogging Sabtu pagi');
});

test('isian pengumuman divalidasi', function (array $data, string $kolom) {
    $this->actingAs($this->admin)
        ->from(route('admin.pengumuman.create'))
        ->post(route('admin.pengumuman.store'), array_merge(['judul' => 'A', 'isi' => 'B', 'tampil_mulai' => '2026-11-20'], $data))
        ->assertSessionHasErrors($kolom);
})->with([
    'judul kosong' => [['judul' => ''], 'judul'],
    'isi kosong' => [['isi' => ''], 'isi'],
    'selesai sebelum mulai' => [['tampil_sampai' => '2026-11-19'], 'tampil_sampai'],
]);

test('beranda penyewa hanya menampilkan pengumuman yang sedang tampil', function () {
    Pengumuman::factory()->create(['judul' => 'Pemadaman air', 'tampil_mulai' => '2026-11-18']);
    Pengumuman::factory()->create(['judul' => 'Lomba 17an', 'tampil_mulai' => '2026-08-01', 'tampil_sampai' => '2026-08-18']);
    Pengumuman::factory()->create(['judul' => 'Kerja bakti', 'tampil_mulai' => '2026-11-25']);

    $this->actingAs($this->penyewa)
        ->get(route('penyewa.beranda'))
        ->assertSee('Pengumuman')
        ->assertSee('Pemadaman air')
        ->assertDontSee('Lomba 17an')
        ->assertDontSee('Kerja bakti');
});

test('admin mengubah dan mengakhiri pengumuman', function () {
    $pengumuman = Pengumuman::factory()->create(['tampil_mulai' => '2026-11-18']);

    $this->actingAs($this->admin)
        ->put(route('admin.pengumuman.update', $pengumuman), ['judul' => 'Pemadaman air diundur', 'isi' => 'Jadi Minggu.', 'tampil_mulai' => '2026-11-18'])
        ->assertRedirect(route('admin.pengumuman.index'));
    expect($pengumuman->fresh()->judul)->toBe('Pemadaman air diundur');

    $this->actingAs($this->admin)
        ->post(route('admin.pengumuman.akhiri', $pengumuman))
        ->assertRedirect(route('admin.pengumuman.index'));
    expect($pengumuman->fresh()->keadaan())->toBe('berakhir');

    expect(ActivityLog::orderBy('id')->pluck('aksi')->all())->toBe([Aksi::PengumumanDiubah, Aksi::PengumumanDiakhiri]);

    $this->actingAs($this->penyewa)->get(route('penyewa.beranda'))->assertDontSee('Pemadaman air diundur');
});

test('daftar pengumuman admin dikelompokkan', function () {
    Pengumuman::factory()->create(['judul' => 'Pemadaman air', 'tampil_mulai' => '2026-11-18']);
    Pengumuman::factory()->create(['judul' => 'Kerja bakti', 'tampil_mulai' => '2026-11-25']);
    Pengumuman::factory()->create(['judul' => 'Lomba 17an', 'tampil_mulai' => '2026-08-01', 'tampil_sampai' => '2026-08-18']);

    $this->actingAs($this->admin)
        ->get(route('admin.pengumuman.index'))
        ->assertSeeInOrder(['Sedang tampil', 'Pemadaman air', 'Terjadwal', 'Kerja bakti', 'Sudah berakhir', 'Lomba 17an']);
});

test('penyewa tidak bisa membuat pengumuman', function () {
    $this->actingAs($this->penyewa)
        ->post(route('admin.pengumuman.store'), ['judul' => 'X', 'isi' => 'Y', 'tampil_mulai' => '2026-11-20'])
        ->assertForbidden();
});
