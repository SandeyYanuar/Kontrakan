<?php

namespace App\Enums;

enum Peran: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Penyewa = 'tenant';

    /**
     * Label untuk ditampilkan di antarmuka.
     */
    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super admin',
            self::Admin => 'Admin',
            self::Penyewa => 'Penyewa',
        };
    }

    /**
     * Super admin dan admin sama-sama mengelola kontrakan.
     */
    public function pengelola(): bool
    {
        return $this !== self::Penyewa;
    }
}
