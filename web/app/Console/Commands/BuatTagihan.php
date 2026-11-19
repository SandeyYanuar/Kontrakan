<?php

namespace App\Console\Commands;

use App\Services\PembuatTagihan;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tagihan:buat {--tanggal= : Anggap hari ini tanggal ini (YYYY-MM-DD), untuk mencoba}')]
#[Description('Buat tagihan sewa yang sudah waktunya dibuat (14 hari sebelum jatuh tempo). Aman dijalankan berulang.')]
class BuatTagihan extends Command
{
    public function handle(PembuatTagihan $pembuat): int
    {
        $tanggal = $this->option('tanggal') ? CarbonImmutable::parse($this->option('tanggal')) : null;
        $jumlah = $pembuat->buatOtomatis($tanggal);

        $this->info("{$jumlah} tagihan baru dibuat.");

        return self::SUCCESS;
    }
}
