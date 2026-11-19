<?php

use App\Enums\Aksi;
use App\Enums\Peran;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('super admin melihat daftar admin', function () {
    $superAdmin = User::factory()->superAdmin()->create(['name' => 'Pangeran']);
    User::factory()->admin()->create(['name' => 'Dewi', 'phone' => '6281200000002']);
    User::factory()->penyewa()->create(['name' => 'Rina']);

    $this->actingAs($superAdmin)
        ->get(route('admin.admin.index'))
        ->assertOk()
        ->assertSee('Dewi')
        ->assertSee('0812-0000-0002')
        ->assertDontSee('Rina');
});

test('super admin menambah admin baru', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)
        ->post(route('admin.admin.store'), [
            'name' => 'Dewi',
            'phone' => '0812 0000 0002',
            'email' => '',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])
        ->assertRedirect(route('admin.admin.index'))
        ->assertSessionHas('status', 'Admin Dewi ditambahkan.');

    $dewi = User::where('phone', '6281200000002')->sole();
    expect($dewi->role)->toBe(Peran::Admin)
        ->and($dewi->is_active)->toBeTrue()
        ->and(Hash::check('rahasia123', $dewi->password))->toBeTrue();

    $log = ActivityLog::sole();
    expect($log->aksi)->toBe(Aksi::AdminDibuat)->and($log->subject->is($dewi))->toBeTrue();
});

test('nomor admin tidak boleh dipakai akun lain', function () {
    User::factory()->penyewa()->create(['phone' => '6281200000002']);

    $this->actingAs(User::factory()->superAdmin()->create())
        ->from(route('admin.admin.create'))
        ->post(route('admin.admin.store'), [
            'name' => 'Dewi',
            'phone' => '081200000002',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])
        ->assertSessionHasErrors(['phone' => 'Nomor HP ini sudah dipakai akun lain.']);
});

test('super admin menonaktifkan lalu mengaktifkan kembali admin', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $dewi = User::factory()->admin()->create();

    $this->actingAs($superAdmin)->patch(route('admin.admin.nonaktifkan', $dewi))->assertRedirect(route('admin.admin.index'));
    expect($dewi->fresh()->is_active)->toBeFalse();

    $this->actingAs($superAdmin)->patch(route('admin.admin.aktifkan', $dewi))->assertRedirect(route('admin.admin.index'));
    expect($dewi->fresh()->is_active)->toBeTrue();

    expect(ActivityLog::orderBy('id')->pluck('aksi')->all())->toBe([Aksi::AdminDinonaktifkan, Aksi::AdminDiaktifkan]);
});

test('super admin mereset password admin', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $dewi = User::factory()->admin()->create();

    $this->actingAs($superAdmin)
        ->patch(route('admin.admin.reset-password', $dewi), ['password' => 'baru12345', 'password_confirmation' => 'baru12345'])
        ->assertRedirect(route('admin.admin.index'));

    expect(Hash::check('baru12345', $dewi->fresh()->password))->toBeTrue()
        ->and(ActivityLog::sole()->aksi)->toBe(Aksi::PasswordAdminDireset)
        ->and(ActivityLog::sole()->data)->toBeNull();
});

test('super admin tidak bisa menonaktifkan dirinya sendiri atau mengelola penyewa lewat menu admin', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $penyewa = User::factory()->penyewa()->create();

    $this->actingAs($superAdmin)->patch(route('admin.admin.nonaktifkan', $superAdmin))->assertNotFound();
    $this->actingAs($superAdmin)->patch(route('admin.admin.nonaktifkan', $penyewa))->assertNotFound();

    expect($superAdmin->fresh()->is_active)->toBeTrue()->and($penyewa->fresh()->is_active)->toBeTrue();
});

test('admin biasa tidak bisa membuka menu admin', function () {
    $admin = User::factory()->admin()->create();
    $lain = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('admin.admin.index'))->assertForbidden();
    $this->actingAs($admin)->patch(route('admin.admin.nonaktifkan', $lain))->assertForbidden();
});
