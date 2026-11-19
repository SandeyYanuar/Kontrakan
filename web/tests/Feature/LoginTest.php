<?php

use App\Models\User;

test('halaman login tampil untuk tamu', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Nomor HP')
        ->assertSee('Ingat saya');
});

test('halaman utama mengarahkan tamu ke login', function () {
    $this->get('/')->assertRedirect(route('login'));
});

test('penyewa bisa login dengan nomor berawalan 08, 62, atau +62', function (string $ketikan) {
    $penyewa = User::factory()->penyewa()->create(['phone' => '6281234567890']);

    $this->post(route('login.store'), ['phone' => $ketikan, 'password' => 'password'])
        ->assertRedirect(route('penyewa.beranda'));

    $this->assertAuthenticatedAs($penyewa);
})->with(['081234567890', '6281234567890', '+62 812-3456-7890']);

test('admin dan super admin diarahkan ke halaman tagihan admin', function (string $peran) {
    $user = User::factory()->{$peran}()->create(['phone' => '6281111111111']);

    $this->post(route('login.store'), ['phone' => '081111111111', 'password' => 'password'])
        ->assertRedirect(route('admin.tagihan'));

    $this->assertAuthenticatedAs($user);
})->with(['admin', 'superAdmin']);

test('nomor yang tidak diawali 08 atau 62 ditolak dengan pesan yang jelas', function () {
    $this->from(route('login'))
        ->post(route('login.store'), ['phone' => '1234567890123', 'password' => 'password'])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['phone' => 'Nomor HP harus diawali 08 atau 62.']);

    $this->assertGuest();
});

test('password salah ditolak tanpa membocorkan nomor terdaftar atau tidak', function () {
    User::factory()->create(['phone' => '6281234567890']);

    $this->from(route('login'))
        ->post(route('login.store'), ['phone' => '081234567890', 'password' => 'salah'])
        ->assertSessionHasErrors(['phone' => 'Nomor HP atau password salah.']);

    $this->from(route('login'))
        ->post(route('login.store'), ['phone' => '089999999999', 'password' => 'salah'])
        ->assertSessionHasErrors(['phone' => 'Nomor HP atau password salah.']);

    $this->assertGuest();
});

test('akun nonaktif tidak bisa login', function () {
    User::factory()->nonaktif()->create(['phone' => '6281234567890']);

    $this->from(route('login'))
        ->post(route('login.store'), ['phone' => '081234567890', 'password' => 'password'])
        ->assertSessionHasErrors(['phone' => 'Akun ini sudah dinonaktifkan. Hubungi pengelola kontrakan.']);

    $this->assertGuest();
});

test('setelah 5 kali salah, login dikunci sementara', function () {
    User::factory()->create(['phone' => '6281234567890']);

    foreach (range(1, 5) as $percobaan) {
        $this->post(route('login.store'), ['phone' => '081234567890', 'password' => 'salah']);
    }

    $this->from(route('login'))
        ->post(route('login.store'), ['phone' => '081234567890', 'password' => 'password'])
        ->assertSessionHasErrors('phone');

    $this->assertGuest();
});

test('ingat saya menyimpan cookie login', function () {
    User::factory()->create(['phone' => '6281234567890']);

    $response = $this->post(route('login.store'), [
        'phone' => '081234567890',
        'password' => 'password',
        'remember' => '1',
    ]);

    $cookie = collect($response->headers->getCookies())
        ->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_'));

    expect($cookie)->not->toBeNull()
        ->and($cookie->getExpiresTime())->toBeLessThanOrEqual(now()->addDays(30)->addMinute()->timestamp)
        ->and($cookie->getExpiresTime())->toBeGreaterThan(now()->addDays(29)->timestamp);
});

test('tanpa ingat saya, tidak ada cookie login jangka panjang', function () {
    User::factory()->create(['phone' => '6281234567890']);

    $response = $this->post(route('login.store'), ['phone' => '081234567890', 'password' => 'password']);

    $cookie = collect($response->headers->getCookies())
        ->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_'));

    expect($cookie)->toBeNull();
});

test('pengguna yang sudah login diarahkan dari halaman login ke halaman awalnya', function () {
    $this->actingAs(User::factory()->penyewa()->create())
        ->get(route('login'))
        ->assertRedirect(route('penyewa.beranda'));
});

test('logout mengakhiri sesi dan kembali ke login', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('link lupa password membuka WhatsApp ke super admin', function () {
    User::factory()->superAdmin()->create(['phone' => '6281200000001']);

    $this->get(route('login'))
        ->assertSee('https://wa.me/6281200000001?text=', false);
});
