<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Akun super admin pertama
    |--------------------------------------------------------------------------
    |
    | Dipakai DatabaseSeeder untuk membuat akun super admin. Setelah login,
    | nomor dan password bisa diganti dari aplikasi.
    |
    */

    'super_admin' => [
        'name' => env('SUPER_ADMIN_NAME', 'Pangeran'),
        'phone' => env('SUPER_ADMIN_PHONE', '081234567890'),
        'password' => env('SUPER_ADMIN_PASSWORD', 'kontrakan123'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Login
    |--------------------------------------------------------------------------
    |
    | Batas percobaan login yang salah per menit untuk satu nomor dan alamat IP.
    | Lama "Ingat saya" (30 hari) diatur di config/auth.php, guard web.
    |
    */

    'batas_percobaan_login' => 5,

];
