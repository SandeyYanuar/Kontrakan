<?php

namespace App\Models;

use App\Enums\Peran;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan kontrakan dalam bentuk kunci dan nilai. Hanya super admin yang mengubahnya.
 */
#[Fillable(['kunci', 'nilai'])]
class Pengaturan extends Model
{
    protected $table = 'pengaturan';

    /**
     * Nilai bawaan sebelum super admin mengisi pengaturan.
     *
     * @var array<string, string|null>
     */
    public const BAWAAN = [
        'nama_kontrakan' => 'Kontrakan',
        'bank_nama' => null,
        'bank_nomor' => null,
        'bank_atas_nama' => null,
        'whatsapp_admin' => null,
        'tanggal_mulai_bayar' => '1',
        'tanggal_jatuh_tempo' => '5',
    ];

    public static function ambil(string $kunci): ?string
    {
        return static::semua()[$kunci] ?? null;
    }

    /**
     * Semua pengaturan, digabung dengan nilai bawaan.
     *
     * @return array<string, string|null>
     */
    public static function semua(): array
    {
        $tersimpan = static::query()->pluck('nilai', 'kunci')->all();

        return array_merge(static::BAWAAN, array_intersect_key($tersimpan, static::BAWAAN));
    }

    /**
     * Simpan beberapa pengaturan sekaligus.
     *
     * @param  array<string, string|null>  $nilai
     */
    public static function simpan(array $nilai): void
    {
        foreach (array_intersect_key($nilai, static::BAWAAN) as $kunci => $isi) {
            static::query()->updateOrCreate(['kunci' => $kunci], ['nilai' => $isi]);
        }
    }

    /**
     * Nomor WhatsApp yang dihubungi penyewa: dari pengaturan, atau nomor super admin pertama.
     */
    public static function nomorWhatsAppAdmin(): ?string
    {
        return static::ambil('whatsapp_admin')
            ?? User::query()
                ->where('role', Peran::SuperAdmin)
                ->where('is_active', true)
                ->oldest('id')
                ->value('phone');
    }
}
