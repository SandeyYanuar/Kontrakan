<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bukti transfer untuk satu tagihan.
 */
#[Fillable(['invoice_id', 'user_id', 'file_path', 'nominal_transfer', 'tgl_transfer', 'nama_pengirim', 'catatan', 'status', 'alasan', 'reviewed_by', 'reviewed_at'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    public const MENUNGGU = 'menunggu';

    public const DISETUJUI = 'disetujui';

    public const DITOLAK = 'ditolak';

    public const DIBATALKAN = 'dibatalkan';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nominal_transfer' => 'integer',
            'tgl_transfer' => 'immutable_date',
            'reviewed_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pengirim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pemeriksa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
