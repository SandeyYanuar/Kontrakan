<?php

namespace App\Models;

use App\Enums\Peran;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'phone', 'email', 'password', 'role', 'is_active', 'kode_penyewa', 'phone_darurat'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => Peran::class,
            'is_active' => 'boolean',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === Peran::SuperAdmin;
    }

    public function isPengelola(): bool
    {
        return $this->role->pengelola();
    }

    public function isPenyewa(): bool
    {
        return $this->role === Peran::Penyewa;
    }

    /**
     * Halaman pertama setelah login, sesuai peran.
     */
    public function halamanAwal(): string
    {
        return $this->isPengelola() ? route('admin.tagihan') : route('penyewa.beranda');
    }
}
