<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['maintenance_ticket_id', 'file_path', 'jenis'])]
class TicketMedia extends Model
{
    protected $table = 'ticket_media';

    /**
     * @return BelongsTo<MaintenanceTicket, $this>
     */
    public function laporan(): BelongsTo
    {
        return $this->belongsTo(MaintenanceTicket::class, 'maintenance_ticket_id');
    }
}
