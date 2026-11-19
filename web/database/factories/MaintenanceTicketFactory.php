<?php

namespace Database\Factories;

use App\Enums\StatusLaporan;
use App\Models\Lease;
use App\Models\MaintenanceTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceTicket>
 */
class MaintenanceTicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lease_id' => Lease::factory(),
            'kategori' => 'air',
            'deskripsi' => 'Keran kamar mandi bocor terus.',
            'status' => StatusLaporan::Diterima,
        ];
    }
}
