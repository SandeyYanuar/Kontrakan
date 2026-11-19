<?php

namespace App\Models;

use Database\Factories\TenantIdentityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Data KTP penyewa (data pribadi, UU 27/2022). NIK terenkripsi, foto di disk privat "local".
 */
#[Fillable(['user_id', 'nik', 'nama_ktp', 'foto_path', 'status', 'alasan', 'verified_by', 'verified_at'])]
#[Hidden(['nik'])]
class TenantIdentity extends Model
{
    /** @use HasFactory<TenantIdentityFactory> */
    use HasFactory;

    public const BELUM_ADA = 'belum_ada';

    public const MENUNGGU = 'menunggu';

    public const SAH = 'sah';

    public const DITOLAK = 'ditolak';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nik' => 'encrypted',
            'verified_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function pemeriksa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Penyewa boleh mengupload atau mengganti KTP selama belum dinyatakan sah.
     */
    public function bisaDiupload(): bool
    {
        return $this->status !== self::SAH;
    }

    public function labelStatus(): string
    {
        return match ($this->status) {
            self::MENUNGGU => 'Menunggu dicek',
            self::SAH => 'Sah',
            self::DITOLAK => 'Perlu upload ulang',
            default => 'Belum ada',
        };
    }
}
