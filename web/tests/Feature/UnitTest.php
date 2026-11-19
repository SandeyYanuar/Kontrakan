<?php

use App\Enums\Aksi;
use App\Models\ActivityLog;
use App\Models\Unit;
use App\Models\User;

test('admin dan super admin bisa melihat daftar unit', function (string $peran) {
    Unit::factory()->create(['kode_unit' => 'A1', 'harga_sewa' => 750000]);

    $this->actingAs(User::factory()->{$peran}()->create())
        ->get(route('admin.unit.index'))
        ->assertOk()
        ->assertSee('A1')
        ->assertSee('Rp 750.000');
})->with(['admin', 'superAdmin']);

test('super admin menambah unit, harga boleh diketik dengan titik', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)
        ->post(route('admin.unit.store'), ['kode_unit' => ' b2 ', 'harga_sewa' => 'Rp 850.000', 'keterangan' => 'Dekat jalan'])
        ->assertRedirect(route('admin.unit.index'))
        ->assertSessionHas('status', 'Unit B2 ditambahkan.');

    $unit = Unit::sole();
    expect($unit->kode_unit)->toBe('B2')->and($unit->harga_sewa)->toBe(850000);

    $log = ActivityLog::sole();
    expect($log->aksi)->toBe(Aksi::UnitDibuat)
        ->and($log->subject->is($unit))->toBeTrue()
        ->and($log->data)->toBe(['kode_unit' => 'B2', 'harga_sewa' => 850000]);
});

test('kode unit tidak boleh dobel', function () {
    Unit::factory()->create(['kode_unit' => 'A1']);

    $this->actingAs(User::factory()->superAdmin()->create())
        ->from(route('admin.unit.create'))
        ->post(route('admin.unit.store'), ['kode_unit' => 'a1', 'harga_sewa' => '750000'])
        ->assertSessionHasErrors('kode_unit');

    expect(Unit::count())->toBe(1);
});

test('perubahan harga dicatat terpisah dengan harga lama dan baru', function () {
    $unit = Unit::factory()->create(['kode_unit' => 'A1', 'harga_sewa' => 750000]);

    $this->actingAs(User::factory()->superAdmin()->create())
        ->put(route('admin.unit.update', $unit), ['kode_unit' => 'A1', 'harga_sewa' => '800.000'])
        ->assertRedirect(route('admin.unit.index'));

    expect($unit->fresh()->harga_sewa)->toBe(800000);

    $log = ActivityLog::sole();
    expect($log->aksi)->toBe(Aksi::HargaUnitDiubah)
        ->and($log->data)->toBe(['kode_unit' => 'A1', 'harga_lama' => 750000, 'harga_baru' => 800000]);
});

test('perubahan selain harga dicatat sebagai ubah unit', function () {
    $unit = Unit::factory()->create(['kode_unit' => 'A1', 'harga_sewa' => 750000, 'keterangan' => null]);

    $this->actingAs(User::factory()->superAdmin()->create())
        ->put(route('admin.unit.update', $unit), ['kode_unit' => 'A1', 'harga_sewa' => '750000', 'keterangan' => 'Ada kanopi']);

    expect(ActivityLog::sole()->aksi)->toBe(Aksi::UnitDiubah);
});

test('admin biasa tidak bisa menambah atau mengubah unit', function () {
    $admin = User::factory()->admin()->create();
    $unit = Unit::factory()->create(['harga_sewa' => 750000]);

    $this->actingAs($admin)->get(route('admin.unit.create'))->assertForbidden();
    $this->actingAs($admin)->post(route('admin.unit.store'), ['kode_unit' => 'C1', 'harga_sewa' => '1'])->assertForbidden();
    $this->actingAs($admin)->put(route('admin.unit.update', $unit), ['kode_unit' => 'X', 'harga_sewa' => '1'])->assertForbidden();

    expect($unit->fresh()->harga_sewa)->toBe(750000);
    $this->actingAs($admin)->get(route('admin.unit.index'))->assertDontSee('Tambah unit');
});
