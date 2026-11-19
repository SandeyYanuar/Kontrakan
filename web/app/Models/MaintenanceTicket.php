<?php

namespace App\Models;

use App\Enums\StatusLaporan;
use Database\Factories\MaintenanceTicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Laporan kerusakan dari penyewa.
 */
#[Fillable(['lease_id', 'kategori', 'deskripsi', 'preferensi_tanggal', 'preferensi_waktu', 'jadwal_kunjungan', 'status', 'selesai_at', 'dikonfirmasi_at'])]
class MaintenanceTicket extends Model
{
    /** @use HasFactory<MaintenanceTicketFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StatusLaporan::class,
            'preferensi_tanggal' => 'immutable_date',
            'jadwal_kunjungan' => 'immutable_datetime',
            'selesai_at' => 'immutable_datetime',
            'dikonfirmasi_at' => 'immutable_datetime',
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
     * @return HasMany<TicketMedia, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(TicketMedia::class);
    }

    public function labelKategori(): string
    {
        return StatusLaporan::kategori()[$this->kategori] ?? $this->kategori;
    }

    public function labelPreferensi(): ?string
    {
        if (! $this->preferensi_tanggal) {
            return null;
        }

        $waktu = $this->preferensi_waktu ? ', '.mb_strtolower(explode(' ', StatusLaporan::waktu()[$this->preferensi_waktu] ?? '')[0]) : '';

        return $this->preferensi_tanggal->locale('id')->translatedFormat('l, j F').$waktu;
    }

    /**
     * Masih perlu ditangani pengelola, atau selesai tapi belum dikonfirmasi penyewa.
     */
    public function aktif(): bool
    {
        return $this->status !== StatusLaporan::Selesai || $this->dikonfirmasi_at === null;
    }
}
