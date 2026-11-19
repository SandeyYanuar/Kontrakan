<?php

namespace Database\Seeders;

use App\Enums\Peran;
use App\Models\User;
use App\Support\NomorHp;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Membuat akun super admin pertama dari config/kontrakan.php
     * (SUPER_ADMIN_PHONE dan SUPER_ADMIN_PASSWORD di .env).
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['phone' => NomorHp::normalisasi(config('kontrakan.super_admin.phone'))],
            [
                'name' => config('kontrakan.super_admin.name'),
                'password' => config('kontrakan.super_admin.password'),
                'role' => Peran::SuperAdmin,
                'is_active' => true,
            ],
        );
    }
}
