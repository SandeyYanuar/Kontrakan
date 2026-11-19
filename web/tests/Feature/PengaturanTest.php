<?php

use App\Enums\Aksi;
use App\Models\ActivityLog;
use App\Models\Pengaturan;
use App\Models\User;

function dataPengaturanSah(array $ubah = []): array
{
    return array_merge([
        'nama_kontrakan' => 'Kontrakan Gunung Kawi',
        'bank_nama' => 'Bank Contoh',
        'bank_nomor' => '1234 5678 4821',
        'bank_atas_nama' => 'Pangeran G. K.',
        'whatsapp_admin' => '0812-0000-0001',
        'tanggal_mulai_bayar' => '1',
        'tanggal_jatuh_tempo' => '5',
    ], $ubah);
}

test('pengaturan punya nilai bawaan sebelum diisi', function () {
    expect(Pengaturan::ambil('nama_kontrakan'))->toBe('Kontrakan')
        ->and(Pengaturan::ambil('tanggal_mulai_bayar'))->toBe('1')
        ->and(Pengaturan::ambil('tanggal_jatuh_tempo'))->toBe('5')
        ->and(Pengaturan::ambil('bank_nomor'))->toBeNull();
});

test('super admin menyimpan pengaturan dan perubahannya tercatat', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)
        ->put(route('admin.pengaturan.update'), dataPengaturanSah())
        ->assertRedirect(route('admin.pengaturan'))
        ->assertSessionHas('status', 'Pengaturan disimpan.');

    expect(Pengaturan::ambil('nama_kontrakan'))->toBe('Kontrakan Gunung Kawi')
        ->and(Pengaturan::ambil('whatsapp_admin'))->toBe('6281200000001')
        ->and(Pengaturan::ambil('bank_nomor'))->toBe('1234 5678 4821');

    $log = ActivityLog::sole();
    expect($log->aksi)->toBe(Aksi::PengaturanDiubah)
        ->and($log->actor_id)->toBe($superAdmin->id)
        ->and($log->data['nama_kontrakan'])->toBe(['lama' => 'Kontrakan', 'baru' => 'Kontrakan Gunung Kawi']);
});

test('menyimpan tanpa perubahan tidak menambah catatan', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)->put(route('admin.pengaturan.update'), dataPengaturanSah());
    $this->actingAs($superAdmin)->put(route('admin.pengaturan.update'), dataPengaturanSah());

    expect(ActivityLog::count())->toBe(1);
});

test('isian pengaturan divalidasi', function (array $ubah, string $kolom) {
    $this->actingAs(User::factory()->superAdmin()->create())
        ->from(route('admin.pengaturan'))
        ->put(route('admin.pengaturan.update'), dataPengaturanSah($ubah))
        ->assertSessionHasErrors($kolom);
})->with([
    'nama kosong' => [['nama_kontrakan' => ''], 'nama_kontrakan'],
    'nomor WhatsApp bukan 08/62' => [['whatsapp_admin' => '1234567890'], 'whatsapp_admin'],
    'tanggal mulai di luar 1-28' => [['tanggal_mulai_bayar' => '29'], 'tanggal_mulai_bayar'],
    'jatuh tempo sebelum tanggal mulai' => [['tanggal_mulai_bayar' => '5', 'tanggal_jatuh_tempo' => '3'], 'tanggal_jatuh_tempo'],
]);

test('admin biasa tidak bisa mengubah pengaturan', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.pengaturan.update'), dataPengaturanSah())
        ->assertForbidden();

    expect(Pengaturan::ambil('nama_kontrakan'))->toBe('Kontrakan');
});

test('lupa password memakai nomor WhatsApp admin dari pengaturan', function () {
    User::factory()->superAdmin()->create(['phone' => '6281200000009']);
    Pengaturan::simpan(['whatsapp_admin' => '6281200000001']);

    $this->get(route('login'))->assertSee('https://wa.me/6281200000001?text=', false);
});

test('nama kontrakan dari pengaturan tampil di halaman login', function () {
    Pengaturan::simpan(['nama_kontrakan' => 'Kontrakan Gunung Kawi']);

    $this->get(route('login'))->assertSee('Kontrakan Gunung Kawi');
});
