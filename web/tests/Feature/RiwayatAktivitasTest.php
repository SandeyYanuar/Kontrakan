<?php

use App\Enums\Aksi;
use App\Exceptions\RiwayatTidakBisaDiubah;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\Aktivitas;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('aktivitas dicatat dengan pelaku yang sedang login, subjek, dan data', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $admin = User::factory()->admin()->create();
    $this->actingAs($superAdmin);

    $log = Aktivitas::catat(Aksi::AdminDinonaktifkan, $admin, ['alasan' => 'berhenti']);

    expect($log->fresh())
        ->actor_id->toBe($superAdmin->id)
        ->aksi->toBe(Aksi::AdminDinonaktifkan)
        ->subject->is($admin)->toBeTrue()
        ->data->toBe(['alasan' => 'berhenti'])
        ->created_at->not->toBeNull();
});

test('aktivitas tanpa pengguna login tercatat sebagai sistem', function () {
    expect(Aktivitas::catat(Aksi::PengaturanDiubah)->actor_id)->toBeNull();
});

test('catatan tidak bisa diubah atau dihapus lewat model', function () {
    $log = ActivityLog::factory()->create();

    expect(fn () => $log->update(['aksi' => Aksi::UnitDibuat]))->toThrow(RiwayatTidakBisaDiubah::class)
        ->and(fn () => $log->delete())->toThrow(RiwayatTidakBisaDiubah::class);
});

test('catatan tidak bisa diubah atau dihapus langsung di database', function () {
    $log = ActivityLog::factory()->create();

    expect(fn () => DB::table('activity_logs')->where('id', $log->id)->update(['aksi' => 'unit.dibuat']))
        ->toThrow(QueryException::class, 'Riwayat aktivitas tidak bisa diubah')
        ->and(fn () => DB::table('activity_logs')->where('id', $log->id)->delete())
        ->toThrow(QueryException::class, 'Riwayat aktivitas tidak bisa dihapus');

    expect(ActivityLog::find($log->id)->aksi)->toBe(Aksi::PengaturanDiubah);
});
