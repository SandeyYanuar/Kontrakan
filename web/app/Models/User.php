<?php

namespace App\Models;

use App\Enums\Peran;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
     * Semua kontrak sewa penyewa ini, termasuk yang sudah selesai.
     *
     * @return HasMany<Lease, $this>
     */
    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    /**
     * Kontrak sewa yang sedang berjalan.
     *
     * @return HasOne<Lease, $this>
     */
    public function leaseAktif(): HasOne
    {
        return $this->hasOne(Lease::class)->where('status', Lease::AKTIF)->latestOfMany();
    }

    /**
     * Data KTP (hanya penyewa).
     *
     * @return HasOne<TenantIdentity, $this>
     */
    public function identitas(): HasOne
    {
        return $this->hasOne(TenantIdentity::class);
    }

    /**
     * Status KTP penyewa, "belum_ada" kalau belum pernah upload.
     */
    public function statusKtp(): string
    {
        return $this->identitas?->status ?? TenantIdentity::BELUM_ADA;
    }

    /**
     * Halaman pertama setelah login, sesuai peran.
     */
    public function halamanAwal(): string
    {
        return $this->isPengelola() ? route('admin.tagihan') : route('penyewa.beranda');
    }
}
