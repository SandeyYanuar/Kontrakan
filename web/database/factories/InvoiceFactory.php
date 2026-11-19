<?php

namespace Database\Factories;

use App\Enums\StatusTagihan;
use App\Models\Invoice;
use App\Models\Lease;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
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
            'periode' => '2026-12',
            'jatuh_tempo' => '2026-12-05',
            'total' => 750000,
            'status' => StatusTagihan::BelumBayar,
        ];
    }

    public function lunas(): static
    {
        return $this->state(fn (array $attributes) => ['status' => StatusTagihan::Lunas]);
    }

    public function menunggu(): static
    {
        return $this->state(fn (array $attributes) => ['status' => StatusTagihan::Menunggu]);
    }

    public function ditolak(): static
    {
        return $this->state(fn (array $attributes) => ['status' => StatusTagihan::Ditolak]);
    }
}
