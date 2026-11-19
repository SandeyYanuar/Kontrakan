<?php

namespace Database\Factories;

use App\Models\Lease;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lease>
 */
class LeaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->penyewa(),
            'unit_id' => Unit::factory(),
            'tgl_mulai' => now()->subMonths(3)->startOfMonth(),
            'tgl_selesai' => null,
            'status' => Lease::AKTIF,
        ];
    }

    public function selesai(): static
    {
        return $this->state(fn (array $attributes) => [
            'tgl_selesai' => now()->subMonth(),
            'status' => Lease::SELESAI,
        ]);
    }
}
