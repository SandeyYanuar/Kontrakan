<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PengumumanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pengumuman di papan pengumuman beranda penyewa.
 */
#[Fillable(['judul', 'isi', 'tampil_mulai', 'tampil_sampai', 'created_by'])]
class Pengumuman extends Model
{
    /** @use HasFactory<PengumumanFactory> */
    use HasFactory;

    protected $table = 'announcements';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tampil_mulai' => 'immutable_date',
            'tampil_sampai' => 'immutable_date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Pengumuman yang sedang tampil hari ini.
     *
     * @param  Builder<Pengumuman>  $query
     */
    public function scopeTampil(Builder $query): void
    {
        $hariIni = CarbonImmutable::today()->toDateString();

        $query->whereDate('tampil_mulai', '<=', $hariIni)
            ->where(fn (Builder $q) => $q->whereNull('tampil_sampai')->orWhereDate('tampil_sampai', '>=', $hariIni));
    }

    /**
     * "tampil", "terjadwal", atau "berakhir".
     */
    public function keadaan(): string
    {
        $hariIni = CarbonImmutable::today();

        return match (true) {
            $this->tampil_mulai->greaterThan($hariIni) => 'terjadwal',
            $this->tampil_sampai !== null && $this->tampil_sampai->lessThan($hariIni) => 'berakhir',
            default => 'tampil',
        };
    }
}
