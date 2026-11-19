<?php

use App\Enums\Aksi;
use App\Enums\Peran;
use App\Models\ActivityLog;
use App\Models\Lease;
use App\Models\Unit;
use App\Models\User;
use App\Support\KodePenyewa;
use Illuminate\Support\Facades\Hash;

function dataPenyewaBaru(Unit $unit, array $ubah = []): array
{
    return array_merge([
        'name' => 'Rina Marlina',
        'phone' => '0812-3456-7781',
        'email' => '',
        'unit_id' => $unit->id,
        'tgl_mulai' => '2026-12-10',
        'password' => 'rina12345',
        'password_confirmation' => 'rina12345',
    ], $ubah);
}

test('kode penyewa berurutan dan tidak dipakai ulang walau penyewa sudah keluar', function () {
    expect(KodePenyewa::berikutnya())->toBe('KT-0001');

    User::factory()->penyewa()->create(['kode_penyewa' => 'KT-0001']);
    User::factory()->penyewa()->nonaktif()->create(['kode_penyewa' => 'KT-0007']);

    expect(KodePenyewa::berikutnya())->toBe('KT-0008');
});

test('admin menambah penyewa sekalian kontrak sewa di unit kosong', function () {
    $admin = User::factory()->admin()->create();
    $unit = Unit::factory()->create(['kode_unit' => 'B1']);

    $this->actingAs($admin)
        ->post(route('admin.penyewa.store'), dataPenyewaBaru($unit))
        ->assertRedirect(route('admin.penyewa.index'))
        ->assertSessionHas('status', 'Rina Marlina (KT-0001) ditambahkan di unit B1.');

    $rina = User::where('phone', '6281234567781')->sole();
    expect($rina->role)->toBe(Peran::Penyewa)
        ->and($rina->kode_penyewa)->toBe('KT-0001')
        ->and(Hash::check('rina12345', $rina->password))->toBeTrue()
        ->and($rina->leaseAktif->unit->is($unit))->toBeTrue()
        ->and($rina->leaseAktif->tgl_mulai->toDateString())->toBe('2026-12-10')
        ->and($unit->fresh()->terisi())->toBeTrue();

    $log = ActivityLog::sole();
    expect($log->aksi)->toBe(Aksi::PenyewaDitambah)
        ->and($log->actor_id)->toBe($admin->id)
        ->and($log->data)->toBe(['kode_penyewa' => 'KT-0001', 'nama' => 'Rina Marlina', 'unit' => 'B1']);
});

test('unit yang sudah terisi tidak bisa dipilih untuk penyewa baru', function () {
    $lease = Lease::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->from(route('admin.penyewa.create'))
        ->post(route('admin.penyewa.store'), dataPenyewaBaru($lease->unit))
        ->assertSessionHasErrors(['unit_id' => 'Unit ini sudah terisi. Pilih unit yang kosong.']);

    expect(User::where('phone', '6281234567781')->exists())->toBeFalse();
});

test('form tambah penyewa hanya menawarkan unit kosong', function () {
    Unit::factory()->create(['kode_unit' => 'A1']);
    Lease::factory()->for(Unit::factory()->state(['kode_unit' => 'A2']))->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.penyewa.create'))
        ->assertOk()
        ->assertSee('A1')
        ->assertDontSee('A2');
});

test('nomor HP penyewa harus unik dan diawali 08 atau 62', function () {
    $unit = Unit::factory()->create();
    User::factory()->admin()->create(['phone' => '6281234567781']);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->from(route('admin.penyewa.create'))
        ->post(route('admin.penyewa.store'), dataPenyewaBaru($unit))
        ->assertSessionHasErrors(['phone' => 'Nomor HP ini sudah dipakai akun lain.']);

    $this->actingAs($admin)->from(route('admin.penyewa.create'))
        ->post(route('admin.penyewa.store'), dataPenyewaBaru($unit, ['phone' => '1234567890']))
        ->assertSessionHasErrors(['phone' => 'Nomor HP harus diawali 08 atau 62.']);
});

test('daftar penyewa menampilkan kode, unit, dan nomor HP', function () {
    $lease = Lease::factory()
        ->for(User::factory()->penyewa()->state(['name' => 'Siti Aminah', 'kode_penyewa' => 'KT-0007', 'phone' => '6281399881200']), 'penyewa')
        ->for(Unit::factory()->state(['kode_unit' => 'A3']))
        ->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.penyewa.index'))
        ->assertOk()
        ->assertSee(['Siti Aminah', 'KT-0007', 'A3', '0813-9988-1200']);
});

test('admin mengubah nomor HP penyewa dan perubahannya tercatat', function () {
    $lease = Lease::factory()->create();
    $penyewa = $lease->penyewa;

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.penyewa.update', $penyewa), [
            'name' => $penyewa->name,
            'phone' => '0857 1122 9034',
            'email' => '',
            'phone_darurat' => '0812 0000 9999',
        ])
        ->assertRedirect(route('admin.penyewa.index'));

    expect($penyewa->fresh()->phone)->toBe('6285711229034')
        ->and($penyewa->fresh()->phone_darurat)->toBe('6281200009999');

    $log = ActivityLog::sole();
    expect($log->aksi)->toBe(Aksi::PenyewaDiubah)
        ->and($log->data['perubahan'])->toContain('phone', 'phone_darurat');
});

test('admin mereset password penyewa', function () {
    $penyewa = Lease::factory()->create()->penyewa;

    $this->actingAs(User::factory()->admin()->create())
        ->patch(route('admin.penyewa.reset-password', $penyewa), ['password' => 'baru12345', 'password_confirmation' => 'baru12345'])
        ->assertRedirect(route('admin.penyewa.index'));

    expect(Hash::check('baru12345', $penyewa->fresh()->password))->toBeTrue()
        ->and(ActivityLog::sole()->aksi)->toBe(Aksi::PasswordPenyewaDireset);
});

test('super admin memindahkan penyewa ke unit lain dengan kode yang sama', function () {
    $lama = Lease::factory()->for(Unit::factory()->state(['kode_unit' => 'A3']))->create();
    $penyewa = $lama->penyewa;
    $kode = $penyewa->kode_penyewa;
    $tujuan = Unit::factory()->create(['kode_unit' => 'B2']);

    $this->actingAs(User::factory()->superAdmin()->create())
        ->post(route('admin.penyewa.pindah', $penyewa), ['unit_id' => $tujuan->id, 'tgl_pindah' => '2027-01-01'])
        ->assertRedirect(route('admin.penyewa.index'));

    $lama->refresh();
    expect($lama->status)->toBe(Lease::SELESAI)
        ->and($lama->tgl_selesai->toDateString())->toBe('2026-12-31')
        ->and($penyewa->fresh()->leaseAktif->unit->is($tujuan))->toBeTrue()
        ->and($penyewa->fresh()->leaseAktif->tgl_mulai->toDateString())->toBe('2027-01-01')
        ->and($penyewa->fresh()->kode_penyewa)->toBe($kode)
        ->and($lama->unit->fresh()->terisi())->toBeFalse();

    expect(ActivityLog::sole()->data)->toBe(['kode_penyewa' => $kode, 'dari' => 'A3', 'ke' => 'B2']);
});

test('super admin mengakhiri sewa: unit kosong dan akun penyewa nonaktif', function () {
    $lease = Lease::factory()->create();

    $this->actingAs(User::factory()->superAdmin()->create())
        ->post(route('admin.penyewa.akhiri', $lease->penyewa), ['tgl_selesai' => '2026-12-31'])
        ->assertRedirect(route('admin.penyewa.index'));

    $lease->refresh();
    expect($lease->status)->toBe(Lease::SELESAI)
        ->and($lease->tgl_selesai->toDateString())->toBe('2026-12-31')
        ->and($lease->penyewa->is_active)->toBeFalse()
        ->and($lease->unit->fresh()->terisi())->toBeFalse()
        ->and(ActivityLog::sole()->aksi)->toBe(Aksi::SewaDiakhiri);
});

test('admin biasa tidak bisa memindahkan penyewa atau mengakhiri sewa', function () {
    $lease = Lease::factory()->create();
    $tujuan = Unit::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.penyewa.pindah', $lease->penyewa), ['unit_id' => $tujuan->id, 'tgl_pindah' => '2027-01-01'])->assertForbidden();
    $this->actingAs($admin)->post(route('admin.penyewa.akhiri', $lease->penyewa), ['tgl_selesai' => '2026-12-31'])->assertForbidden();

    expect($lease->fresh()->status)->toBe(Lease::AKTIF);
});

test('menu penyewa tidak bisa dipakai untuk mengubah akun admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs(User::factory()->superAdmin()->create())
        ->put(route('admin.penyewa.update', $admin), ['name' => 'X', 'phone' => '081200000000'])
        ->assertNotFound();
});
