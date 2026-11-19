<?php

use App\Support\NomorHp;

test('nomor diawali 08, 62, atau +62 disimpan dalam format 62', function (string $masukan, string $hasil) {
    expect(NomorHp::normalisasi($masukan))->toBe($hasil);
})->with([
    'diawali 08' => ['081234567890', '6281234567890'],
    'diawali 62' => ['6281234567890', '6281234567890'],
    'diawali +62' => ['+6281234567890', '6281234567890'],
    'dengan spasi dan strip' => ['0812-3456 7890', '6281234567890'],
    'dengan titik dan kurung' => ['(0812) 3456.7890', '6281234567890'],
    'nomor pendek 10 digit' => ['0812345678', '62812345678'],
    'nomor panjang 13 digit' => ['0812345678901', '62812345678901'],
]);

test('nomor yang tidak diawali 08 atau 62 ditolak', function (string $masukan) {
    expect(NomorHp::normalisasi($masukan))->toBeNull();
})->with([
    'angka acak' => ['1234567890123'],
    'telepon rumah' => ['0215551234'],
    'kode negara lain' => ['+6591234567'],
    'terlalu pendek' => ['08123'],
    'terlalu panjang' => ['08123456789012'],
    'ada huruf' => ['0812abc67890'],
    'kosong' => [''],
]);

test('nomor ditampilkan dalam format 08 yang mudah dibaca', function () {
    expect(NomorHp::tampil('6281234567890'))->toBe('0812-3456-7890');
});
