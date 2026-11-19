<?php

namespace Database\Factories;

use App\Models\Pengumuman;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pengumuman>
 */
class PengumumanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'judul' => 'Pemadaman air',
            'isi' => 'Air PDAM mati Sabtu pukul 08.00 sampai 12.00. Mohon tampung air sebelumnya.',
            'tampil_mulai' => now()->toDateString(),
            'tampil_sampai' => null,
            'created_by' => User::factory()->admin(),
        ];
    }
}
