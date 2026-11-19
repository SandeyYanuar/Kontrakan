<?php

use Illuminate\Support\Facades\Schedule;

/*
 * Tagihan sewa dibuat setiap hari dini hari. Di komputer lokal jalankan `php artisan schedule:work`,
 * atau cukup buka halaman Tagihan admin: tagihan yang belum ada ikut dibuat saat itu.
 */
Schedule::command('tagihan:buat')->dailyAt('00:10');
