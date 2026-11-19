<?php

use App\Enums\Aksi;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Unit;
use App\Models\User;
use App\Services\TagihanBulan;
use App\Support\BarisTagihan;
use Carbon\CarbonImmutable;

/**
 * Penyewa aktif di unit tertentu, dicatat sebelum tagihan pertama supaya tidak ada tagihan otomatis tambahan.
 */
function penyewaDi(string $kodeUnit, string $nama, string $kode, int $harga = 750000): Lease
{
    return Lease::factory()
        ->for(User::factory()->penyewa()->state(['name' => $nama, 'kode_penyewa' => $kode]), 'penyewa')
        ->for(Unit::factory()->state(['kode_unit' => $kodeUnit, 'harga_sewa' => $harga]))
        ->create(['tgl_mulai' => '2026-11-10', 'created_at' => '2026-11-10']);
}

function tagihan(Lease $lease, string $periode, string $status = 'belum_bayar'): Invoice
{
    return Invoice::factory()->for($lease)->create([
        'periode' => $periode,
        'jatuh_tempo' => $periode.'-05',
        'total' => $lease->unit->harga_sewa,
        'status' => $status,
    ]);
}

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-12-09 10:00'));
    $this->admin = User::factory()->admin()->create();
});

test('bulan bawaan pindah ke bulan depan saat periode bayarnya dimulai', function () {
    expect(TagihanBulan::periodeAktif(CarbonImmutable::parse('2026-11-26')))->toBe('2026-11')
        ->and(TagihanBulan::periodeAktif(CarbonImmutable::parse('2026-11-27')))->toBe('2026-12')
        ->and(TagihanBulan::periodeAktif(CarbonImmutable::parse('2026-12-09')))->toBe('2026-12');
});

test('baris dikelompokkan dan yang paling mendesak di atas', function () {
    $lunas = penyewaDi('A1', 'Bambang', 'KT-0001');
    tagihan($lunas, '2026-12', 'lunas');
    $dicek = penyewaDi('A3', 'Siti', 'KT-0007');
    tagihan($dicek, '2026-12', 'menunggu');
    $telat = penyewaDi('B1', 'Rina', 'KT-0003', 850000);
    tagihan($telat, '2026-12');
    penyewaDi('B2', 'Joko', 'KT-0012');

    $baris = app(TagihanBulan::class)->untuk('2026-12')['baris'];

    expect($baris->map(fn (BarisTagihan $b) => [$b->lease->unit->kode_unit, $b->kelompok(), $b->teksStatus()])->all())->toBe([
        ['B1', BarisTagihan::KELOMPOK_TINDAK, 'Telat 4 hari'],
        ['A3', BarisTagihan::KELOMPOK_TINDAK, 'Bukti perlu dicek'],
        ['B2', BarisTagihan::KELOMPOK_BELUM_WAKTUNYA, 'Belum ada tagihan'],
        ['A1', BarisTagihan::KELOMPOK_LUNAS, 'Lunas'],
    ]);
});

test('tunggakan bulan lalu tetap muncul dan menentukan warna pintu', function () {
    $lease = penyewaDi('B1', 'Rina', 'KT-0003');
    tagihan($lease, '2026-11');
    tagihan($lease, '2027-01');

    $this->travelTo(CarbonImmutable::parse('2026-12-28 10:00'));
    $data = app(TagihanBulan::class)->untuk('2027-01');
    $baris = $data['baris']->sole();

    expect($baris->status())->toBe(Invoice::LABEL_TELAT)
        ->and($baris->tunggakan()->pluck('periode')->all())->toBe(['2026-11'])
        ->and($baris->totalHarusDibayar())->toBe(1500000)
        ->and($data['ringkasan']['tunggakan'])->toBe(750000)
        ->and($data['ringkasan']['jumlah_tunggakan'])->toBe(1);
});

test('ringkasan menghitung uang masuk dari tagihan bulan terpilih', function () {
    tagihan(penyewaDi('A1', 'Bambang', 'KT-0001'), '2026-12', 'lunas');
    tagihan(penyewaDi('A2', 'Yuli', 'KT-0002'), '2026-12', 'lunas');
    tagihan(penyewaDi('B1', 'Rina', 'KT-0003', 850000), '2026-12');

    $ringkasan = app(TagihanBulan::class)->untuk('2026-12')['ringkasan'];

    expect($ringkasan['masuk'])->toBe(1500000)
        ->and($ringkasan['tagihan'])->toBe(2350000)
        ->and($ringkasan['per_label'])->toEqual([Invoice::LABEL_LUNAS => 1500000, Invoice::LABEL_TELAT => 850000]);
});

test('halaman tagihan menampilkan deretan pintu, kelompok, dan tombol yang sesuai', function () {
    tagihan(penyewaDi('A1', 'Bambang', 'KT-0001'), '2026-12', 'lunas');
    tagihan(penyewaDi('A3', 'Siti Aminah', 'KT-0007'), '2026-12', 'menunggu');
    tagihan(penyewaDi('B1', 'Rina Marlina', 'KT-0003', 850000), '2026-12');
    Unit::factory()->create(['kode_unit' => 'B4']);

    $this->actingAs($this->admin)
        ->get(route('admin.tagihan', ['bulan' => '2026-12']))
        ->assertOk()
        ->assertSee('Tagihan Desember 2026')
        ->assertSeeInOrder(['Rp 750.000', 'masuk dari Rp 2.350.000'])
        ->assertSeeInOrder(['Perlu ditindak (2)', 'Rina Marlina', 'Siti Aminah', 'Sudah lunas (1)', 'Bambang'])
        ->assertSee('Ingatkan via WhatsApp')
        ->assertSee('Cek bukti transfer')
        ->assertSee('KT-0007')
        ->assertSee('Kosong')
        ->assertSee('aria-label="Lunas"', false);
});

test('pengingat WhatsApp tercatat lalu membuka WhatsApp dengan pesan terisi', function () {
    $lease = penyewaDi('B1', 'Rina Marlina', 'KT-0003', 850000);
    $lease->penyewa->update(['phone' => '6281234567781']);
    tagihan($lease, '2026-11');
    tagihan($lease, '2026-12');

    $response = $this->actingAs($this->admin)->post(route('admin.tagihan.ingatkan', $lease), ['bulan' => '2026-12']);

    $url = $response->headers->get('Location');
    expect($url)->toStartWith('https://wa.me/6281234567781?text=');

    $pesan = rawurldecode(substr($url, strpos($url, 'text=') + 5));
    expect($pesan)
        ->toContain('Halo Rina Marlina (KT-0003)')
        ->toContain('unit B1')
        ->toContain('- November 2026: Rp 850.000')
        ->toContain('- Desember 2026: Rp 850.000')
        ->toContain('Total Rp 1.700.000')
        ->toContain('tulis KT-0003 di berita transfer');

    $log = ActivityLog::sole();
    expect($log->aksi)->toBe(Aksi::PengingatWhatsApp)
        ->and($log->actor_id)->toBe($this->admin->id)
        ->and($log->data['periode'])->toBe(['2026-11', '2026-12'])
        ->and($log->data['total'])->toBe(1700000);
});

test('penyewa tanpa tagihan yang harus dibayar tidak diingatkan', function () {
    $lease = penyewaDi('A1', 'Bambang', 'KT-0001');
    tagihan($lease, '2026-12', 'lunas');

    $this->actingAs($this->admin)
        ->from(route('admin.tagihan'))
        ->post(route('admin.tagihan.ingatkan', $lease), ['bulan' => '2026-12'])
        ->assertRedirect(route('admin.tagihan'));

    expect(ActivityLog::count())->toBe(0);
});

test('penyewa tidak bisa memakai tombol pengingat', function () {
    $lease = penyewaDi('B1', 'Rina', 'KT-0003');

    $this->actingAs($lease->penyewa)
        ->post(route('admin.tagihan.ingatkan', $lease))
        ->assertForbidden();
});
