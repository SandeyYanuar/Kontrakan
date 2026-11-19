<?php

use App\Enums\Aksi;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('pengguna mengganti password sendiri dengan password lama yang benar', function (string $peran) {
    $user = User::factory()->{$peran}()->create();

    $this->actingAs($user)->get(route('akun.password'))->assertOk();

    $this->actingAs($user)
        ->put(route('akun.password.update'), [
            'password_lama' => 'password',
            'password' => 'passwordbaru1',
            'password_confirmation' => 'passwordbaru1',
        ])
        ->assertRedirect(route('akun.password'))
        ->assertSessionHas('status', 'Password diganti.');

    expect(Hash::check('passwordbaru1', $user->fresh()->password))->toBeTrue()
        ->and(ActivityLog::sole()->aksi)->toBe(Aksi::PasswordDiganti);
})->with(['superAdmin', 'admin', 'penyewa']);

test('password lama yang salah ditolak', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->from(route('akun.password'))
        ->put(route('akun.password.update'), [
            'password_lama' => 'salah',
            'password' => 'passwordbaru1',
            'password_confirmation' => 'passwordbaru1',
        ])
        ->assertSessionHasErrors(['password_lama' => 'Password lama salah.']);

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

test('password baru minimal 8 karakter dan harus dikonfirmasi', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->from(route('akun.password'))
        ->put(route('akun.password.update'), [
            'password_lama' => 'password',
            'password' => 'pendek',
            'password_confirmation' => 'beda',
        ])
        ->assertSessionHasErrors('password');
});
