<?php

use App\Support\Format;
use Carbon\CarbonImmutable;

test('rupiah memakai titik sebagai pemisah ribuan', function (int $nominal, string $hasil) {
    expect(Format::rupiah($nominal))->toBe($hasil);
})->with([
    [0, 'Rp 0'],
    [750000, 'Rp 750.000'],
    [4700000, 'Rp 4.700.000'],
    [-50000, '-Rp 50.000'],
]);

test('tanggal ditulis lengkap dalam bahasa Indonesia', function () {
    expect(Format::tanggal(CarbonImmutable::create(2026, 12, 5)))->toBe('5 Desember 2026');
});

test('tanggal pendek memakai singkatan bulan', function () {
    expect(Format::tanggalPendek(CarbonImmutable::create(2026, 11, 18)))->toBe('18 Nov');
});

test('bulan menerima periode YYYY-MM', function () {
    expect(Format::bulan('2026-02'))->toBe('Februari 2026');
});

test('NIK tersamar menampilkan 3 digit depan dan 3 digit belakang', function () {
    expect(Format::nikTersamar('3201234567890004'))->toBe('320••••••••••004')
        ->and(Format::nikTersamar(null))->toBe('');
});

test('terbilang menulis nominal dengan kata dalam bahasa Indonesia', function (int $angka, string $kata) {
    expect(Format::terbilang($angka))->toBe($kata);
})->with([
    [0, 'nol rupiah'],
    [11, 'sebelas rupiah'],
    [100, 'seratus rupiah'],
    [1000, 'seribu rupiah'],
    [750000, 'tujuh ratus lima puluh ribu rupiah'],
    [850000, 'delapan ratus lima puluh ribu rupiah'],
    [1250500, 'satu juta dua ratus lima puluh ribu lima ratus rupiah'],
]);
