<?php

namespace App\Models;

use App\Enums\Aksi;
use App\Exceptions\RiwayatTidakBisaDiubah;
use Database\Factories\ActivityLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Satu catatan di riwayat aktivitas. Hanya bisa ditambah, tidak pernah diubah atau dihapus.
 * Buat lewat App\Support\Aktivitas::catat().
 */
#[Fillable(['actor_id', 'aksi', 'subject_type', 'subject_id', 'data'])]
class ActivityLog extends Model
{
    /** @use HasFactory<ActivityLogFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn () => throw new RiwayatTidakBisaDiubah);
        static::deleting(fn () => throw new RiwayatTidakBisaDiubah);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aksi' => Aksi::class,
            'data' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
