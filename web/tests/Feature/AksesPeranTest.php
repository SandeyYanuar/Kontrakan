<?php

use App\Models\User;

test('tamu diarahkan ke login dari halaman admin dan penyewa', function (string $rute) {
    $this->get(route($rute))->assertRedirect(route('login'));
})->with(['admin.tagihan', 'admin.pengaturan', 'penyewa.beranda']);

test('penyewa tidak bisa membuka halaman admin', function () {
    $this->actingAs(User::factory()->penyewa()->create())
        ->get(route('admin.tagihan'))
        ->assertForbidden();
});

test('admin dan super admin tidak membuka beranda penyewa', function (string $peran) {
    $this->actingAs(User::factory()->{$peran}()->create())
        ->get(route('penyewa.beranda'))
        ->assertForbidden();
})->with(['admin', 'superAdmin']);

test('admin bisa membuka tagihan tapi tidak menu khusus super admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('admin.tagihan'))
        ->assertOk()
        ->assertDontSee('Pengaturan');

    $this->actingAs($admin)->get(route('admin.pengaturan'))->assertForbidden();
});

test('super admin melihat dan bisa membuka menu khusus super admin', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)->get(route('admin.tagihan'))
        ->assertOk()
        ->assertSee('Pengaturan');

    $this->actingAs($superAdmin)->get(route('admin.pengaturan'))->assertOk();
});

test('akun yang dinonaktifkan saat sedang login langsung dikeluarkan', function () {
    $admin = User::factory()->admin()->create();
    $admin->update(['is_active' => false]);

    $this->actingAs($admin)
        ->get(route('admin.tagihan'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
