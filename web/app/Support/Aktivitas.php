<?php

namespace App\Support;

use App\Enums\Aksi;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Pintu satu-satunya untuk menulis riwayat aktivitas.
 */
class Aktivitas
{
    /**
     * Catat satu aktivitas. Pelaku diambil dari pengguna yang sedang login kecuali diberikan.
     *
     * @param  array<string, mixed>  $data
     */
    public static function catat(Aksi $aksi, ?Model $subjek = null, array $data = [], ?User $pelaku = null): ActivityLog
    {
        return ActivityLog::create([
            'actor_id' => ($pelaku ?? auth()->user())?->getKey(),
            'aksi' => $aksi,
            'subject_type' => $subjek?->getMorphClass(),
            'subject_id' => $subjek?->getKey(),
            'data' => $data === [] ? null : $data,
        ]);
    }
}
