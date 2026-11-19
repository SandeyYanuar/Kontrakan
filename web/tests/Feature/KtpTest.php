<?php

use App\Enums\Aksi;
use App\Models\ActivityLog;
use App\Models\Lease;
use App\Models\TenantIdentity;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->penyewa = Lease::factory()->create()->penyewa;
});

function dataKtp(array $ubah = []): array
{
    return array_merge([
        'foto' => UploadedFile::fake()->image('ktp.jpg', 800, 500)->size(500),
        'nik' => '3201 2345 6789 0004',
        'nama_ktp' => 'Rina Marlina',
    ], $ubah);
}

test('beranda penyewa menampilkan banner kalau KTP belum diupload', function () {
    $this->actingAs($this->penyewa)
        ->get(route('penyewa.beranda'))
        ->assertSee('Lengkapi data KTP');

    TenantIdentity::factory()->sah()->for($this->penyewa, 'penyewa')->create();

    $this->actingAs($this->penyewa->fresh())
        ->get(route('penyewa.beranda'))
        ->assertDontSee('Lengkapi data KTP');
});

test('penyewa mengupload KTP; NIK tersimpan terenkripsi dan foto di storage privat', function () {
    $this->actingAs($this->penyewa)
        ->post(route('penyewa.ktp.simpan'), dataKtp())
        ->assertRedirect(route('penyewa.ktp'))
        ->assertSessionHas('status', 'KTP terkirim. Pengelola akan mengeceknya.');

    $identitas = $this->penyewa->fresh()->identitas;
    expect($identitas->nik)->toBe('3201234567890004')
        ->and($identitas->nama_ktp)->toBe('Rina Marlina')
        ->and($identitas->status)->toBe(TenantIdentity::MENUNGGU)
        ->and(DB::table('tenant_identities')->value('nik'))->not->toContain('3201234567890004');

    Storage::disk('local')->assertExists($identitas->foto_path);
    expect($identitas->foto_path)->toStartWith('ktp/');

    $log = ActivityLog::sole();
    expect($log->aksi)->toBe(Aksi::KtpDiupload)
        ->and($log->actor_id)->toBe($this->penyewa->id)
        ->and($log->data)->not->toHaveKey('nik');
});

test('isian KTP divalidasi', function (array $ubah, string $kolom) {
    $this->actingAs($this->penyewa)
        ->from(route('penyewa.ktp'))
        ->post(route('penyewa.ktp.simpan'), dataKtp($ubah))
        ->assertSessionHasErrors($kolom);
})->with([
    'NIK kurang dari 16 digit' => [['nik' => '320123'], 'nik'],
    'NIK ada huruf' => [['nik' => '32012345678900AB'], 'nik'],
    'foto bukan gambar' => [['foto' => UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf')], 'foto'],
    'foto lebih dari 2 MB' => [['foto' => UploadedFile::fake()->image('ktp.jpg')->size(2100)], 'foto'],
    'nama kosong' => [['nama_ktp' => ''], 'nama_ktp'],
]);

test('KTP yang sudah sah tidak bisa diganti penyewa', function () {
    TenantIdentity::factory()->sah()->for($this->penyewa, 'penyewa')->create(['nama_ktp' => 'Lama']);

    $this->actingAs($this->penyewa)
        ->post(route('penyewa.ktp.simpan'), dataKtp())
        ->assertForbidden();

    expect($this->penyewa->fresh()->identitas->nama_ktp)->toBe('Lama');
});

test('admin melihat detail penyewa dengan NIK tersamar', function () {
    TenantIdentity::factory()->for($this->penyewa, 'penyewa')->create(['nik' => '3201234567890004']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.penyewa.show', $this->penyewa))
        ->assertOk()
        ->assertSee('320••••••••••004')
        ->assertDontSee('3201234567890004');
});

test('membuka foto KTP oleh admin tercatat di riwayat', function () {
    $this->actingAs($this->penyewa)->post(route('penyewa.ktp.simpan'), dataKtp());
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('admin.penyewa.ktp.foto', $this->penyewa))->assertOk();

    $log = ActivityLog::where('aksi', Aksi::KtpDilihat)->sole();
    expect($log->actor_id)->toBe($admin->id)
        ->and($log->subject->is($this->penyewa))->toBeTrue();
});

test('penyewa hanya bisa membuka foto KTP miliknya sendiri', function () {
    $this->actingAs($this->penyewa)->post(route('penyewa.ktp.simpan'), dataKtp());
    $lain = Lease::factory()->create()->penyewa;

    $this->actingAs($this->penyewa)->get(route('penyewa.ktp.foto'))->assertOk();
    $this->actingAs($lain)->get(route('penyewa.ktp.foto'))->assertNotFound();
    $this->actingAs($lain)->get(route('admin.penyewa.ktp.foto', $this->penyewa))->assertForbidden();
});

test('admin menandai KTP sah', function () {
    $identitas = TenantIdentity::factory()->for($this->penyewa, 'penyewa')->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.penyewa.ktp.sah', $this->penyewa))
        ->assertRedirect(route('admin.penyewa.show', $this->penyewa));

    $identitas->refresh();
    expect($identitas->status)->toBe(TenantIdentity::SAH)
        ->and($identitas->verified_by)->toBe($admin->id)
        ->and(ActivityLog::sole()->aksi)->toBe(Aksi::KtpDisahkan);
});

test('admin meminta upload ulang dengan alasan yang wajib diisi', function () {
    $identitas = TenantIdentity::factory()->for($this->penyewa, 'penyewa')->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('admin.penyewa.show', $this->penyewa))
        ->post(route('admin.penyewa.ktp.tolak', $this->penyewa), ['alasan' => ''])
        ->assertSessionHasErrors(['alasan' => 'Tulis alasannya supaya penyewa tahu apa yang perlu diperbaiki.']);

    $this->actingAs($admin)
        ->post(route('admin.penyewa.ktp.tolak', $this->penyewa), ['alasan' => 'Foto buram'])
        ->assertRedirect(route('admin.penyewa.show', $this->penyewa));

    expect($identitas->fresh()->status)->toBe(TenantIdentity::DITOLAK)
        ->and($identitas->fresh()->alasan)->toBe('Foto buram')
        ->and(ActivityLog::sole()->data['alasan'])->toBe('Foto buram');

    $this->actingAs($this->penyewa)->get(route('penyewa.ktp'))->assertSee('Foto buram');
});

test('super admin menghapus foto KTP penyewa yang sudah keluar', function () {
    $this->actingAs($this->penyewa)->post(route('penyewa.ktp.simpan'), dataKtp());
    $path = $this->penyewa->fresh()->identitas->foto_path;
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)
        ->delete(route('admin.penyewa.ktp.hapus', $this->penyewa))
        ->assertStatus(422);

    $this->penyewa->update(['is_active' => false]);

    $this->actingAs($superAdmin)
        ->delete(route('admin.penyewa.ktp.hapus', $this->penyewa))
        ->assertRedirect(route('admin.penyewa.show', $this->penyewa));

    Storage::disk('local')->assertMissing($path);
    expect($this->penyewa->fresh()->identitas)
        ->foto_path->toBeNull()
        ->nik->toBeNull()
        ->status->toBe(TenantIdentity::BELUM_ADA);
    expect(ActivityLog::where('aksi', Aksi::KtpDihapus)->count())->toBe(1);
});

test('admin biasa tidak bisa menghapus foto KTP', function () {
    $this->penyewa->update(['is_active' => false]);
    TenantIdentity::factory()->for($this->penyewa, 'penyewa')->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.penyewa.ktp.hapus', $this->penyewa))
        ->assertForbidden();
});

test('daftar penyewa menampilkan status KTP', function () {
    TenantIdentity::factory()->for($this->penyewa, 'penyewa')->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.penyewa.index'))
        ->assertSee('KTP menunggu dicek');
});
