<?php

namespace App\Models;

use App\Enums\StatusTagihan;
use App\Support\PeriodeBayar;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tagihan sewa satu periode (YYYY-MM) untuk satu kontrak sewa.
 */
#[Fillable(['lease_id', 'periode', 'jatuh_tempo', 'total', 'status'])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    public const LABEL_LUNAS = 'lunas';

    public const LABEL_MENUNGGU = 'menunggu';

    public const LABEL_DITOLAK = 'ditolak';

    public const LABEL_TELAT = 'telat';

    public const LABEL_DEKAT = 'dekat';

    public const LABEL_BELUM_PERIODE = 'belum_periode';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jatuh_tempo' => 'immutable_date',
            'total' => 'integer',
            'status' => StatusTagihan::class,
        ];
    }

    /**
     * @return BelongsTo<Lease, $this>
     */
    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Label waktu untuk tampilan dan warna pintu:
     * lunas, menunggu, ditolak, telat (lewat jatuh tempo), dekat (dalam periode bayar), atau belum_periode.
     */
    public function label(?CarbonInterface $hariIni = null): string
    {
        $hariIni = CarbonImmutable::parse($hariIni ?? now())->startOfDay();

        return match (true) {
            $this->status === StatusTagihan::Lunas => self::LABEL_LUNAS,
            $this->status === StatusTagihan::Menunggu => self::LABEL_MENUNGGU,
            $this->status === StatusTagihan::Ditolak => self::LABEL_DITOLAK,
            $hariIni->greaterThan($this->jatuh_tempo) => self::LABEL_TELAT,
            $hariIni->greaterThanOrEqualTo(PeriodeBayar::mulaiBayar($this->periode)) => self::LABEL_DEKAT,
            default => self::LABEL_BELUM_PERIODE,
        };
    }

    /**
     * Jumlah hari lewat jatuh tempo (0 kalau belum lewat).
     */
    public function hariTelat(?CarbonInterface $hariIni = null): int
    {
        $hariIni = CarbonImmutable::parse($hariIni ?? now())->startOfDay();

        return $hariIni->greaterThan($this->jatuh_tempo) ? (int) $this->jatuh_tempo->diffInDays($hariIni) : 0;
    }
}
