<?php

use App\Enums\StatusTagihan;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\TenantIdentity;
use App\Models\Unit;
use App\Models\User;
use App\Services\TagihanBulan;
use App\Support\BarisTagihan;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

test('data contoh berisi unit dan penyewa dengan status yang berbeda-beda', function (string $hariIni) {
    $this->travelTo(CarbonImmutable::parse($hariIni));
    $this->seed(DemoSeeder::class);

    expect(Unit::count())->toBe(9)
        ->and(User::whereNotNull('kode_penyewa')->count())->toBe(8)
        ->and(Lease::aktif()->count())->toBe(7)
        ->and(User::where('kode_penyewa', 'KT-0008')->value('is_active'))->toBeFalse();

    $status = app(TagihanBulan::class)->untuk(TagihanBulan::periodeAktif())['baris']
        ->mapWithKeys(fn (BarisTagihan $b) => [$b->lease->penyewa->kode_penyewa => $b->status()]);

    expect($status['KT-0001'])->toBe(Invoice::LABEL_LUNAS)
        ->and($status['KT-0002'])->toBeIn([Invoice::LABEL_DEKAT, Invoice::LABEL_TELAT, Invoice::LABEL_BELUM_PERIODE])
        ->and($status['KT-0003'])->toBe(Invoice::LABEL_MENUNGGU)
        ->and($status['KT-0004'])->toBe(Invoice::LABEL_DITOLAK)
        ->and($status['KT-0005'])->toBe(Invoice::LABEL_TELAT)
        ->and($status['KT-0006'])->toBe(BarisTagihan::TANPA_TAGIHAN)
        ->and($status['KT-0007'])->toBe(Invoice::LABEL_LUNAS);

    expect(TenantIdentity::pluck('status')->unique()->sort()->values()->all())
        ->toBe([TenantIdentity::DITOLAK, TenantIdentity::MENUNGGU, TenantIdentity::SAH]);

    expect(ActivityLog::count())->toBeGreaterThan(10);
    expect(Invoice::where('status', StatusTagihan::Lunas)->count())->toBeGreaterThan(5);
})->with(['2026-11-20 10:00', '2026-11-28 10:00', '2026-12-08 10:00']);

test('data contoh bisa dijalankan dua kali tanpa dobel', function () {
    $this->seed(DemoSeeder::class);
    $jumlahTagihan = Invoice::count();
    $this->seed(DemoSeeder::class);

    expect(Unit::count())->toBe(9)
        ->and(User::whereNotNull('kode_penyewa')->count())->toBe(8)
        ->and(Invoice::count())->toBe($jumlahTagihan);
});
