<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris rincian tagihan (sewa, listrik, air, denda, lain).
 */
#[Fillable(['invoice_id', 'jenis', 'keterangan', 'nominal'])]
class InvoiceItem extends Model
{
    public const SEWA = 'sewa';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['nominal' => 'integer'];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
