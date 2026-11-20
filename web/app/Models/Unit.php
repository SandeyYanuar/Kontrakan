<?php

namespace App\Models;

use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['kode_unit', 'harga_sewa', 'keterangan', 'warna'])]
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use HasFactory;

    /**
     * Pilihan warna logo rumah unit. Sengaja tidak sama persis dengan warna status tagihan.
     *
     * @var list<string>
     */
    public const PALET = ['#2e6a4c', '#2c4f86', '#b07415', '#6b4f8a', '#1f7a7a', '#8a5a3b', '#b4507a', '#5f7d2e', '#3b4656', '#c2410c', '#0e7490', '#7c2d12'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'harga_sewa' => 'integer',
        ];
    }

    /**
     * @return HasMany<Lease, $this>
     */
    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    /**
     * Kontrak sewa yang sedang menempati unit ini.
     *
     * @return HasOne<Lease, $this>
     */
    public function leaseAktif(): HasOne
    {
        return $this->hasOne(Lease::class)->where('status', Lease::AKTIF)->latestOfMany();
    }

    /**
     * Unit tanpa kontrak sewa aktif.
     *
     * @param  Builder<Unit>  $query
     */
    public function scopeKosong(Builder $query): void
    {
        $query->whereDoesntHave('leases', fn (Builder $lease) => $lease->where('status', Lease::AKTIF));
    }

    public function terisi(): bool
    {
        return $this->leaseAktif !== null;
    }

    /**
     * Warna khas unit untuk logo rumahnya.
     */
    public function warnaLogo(): string
    {
        return $this->warna ?? self::PALET[0];
    }

    /**
     * Warna pertama di palet yang belum dipakai unit lain, untuk unit baru.
     */
    public static function warnaBerikutnya(): string
    {
        $dipakai = static::query()->pluck('warna')->filter()->all();
        $kosong = array_values(array_diff(self::PALET, $dipakai));

        return $kosong[0] ?? self::PALET[count($dipakai) % count(self::PALET)];
    }

    protected static function booted(): void
    {
        static::creating(function (Unit $unit): void {
            $unit->warna ??= static::warnaBerikutnya();
        });
    }
}
