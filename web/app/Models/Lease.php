<?php

namespace App\Models;

use Database\Factories\LeaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kontrak sewa satu penyewa di satu unit.
 */
#[Fillable(['user_id', 'unit_id', 'tgl_mulai', 'tgl_selesai', 'status'])]
class Lease extends Model
{
    /** @use HasFactory<LeaseFactory> */
    use HasFactory;

    public const AKTIF = 'aktif';

    public const SELESAI = 'selesai';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tgl_mulai' => 'immutable_date',
            'tgl_selesai' => 'immutable_date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function penyewa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @param  Builder<Lease>  $query
     */
    public function scopeAktif(Builder $query): void
    {
        $query->where('status', self::AKTIF);
    }
}
