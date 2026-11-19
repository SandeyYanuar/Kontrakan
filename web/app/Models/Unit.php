<?php

namespace App\Models;

use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['kode_unit', 'harga_sewa', 'keterangan'])]
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use HasFactory;

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
}
