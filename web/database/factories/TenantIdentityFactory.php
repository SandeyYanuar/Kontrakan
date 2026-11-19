<?php

namespace Database\Factories;

use App\Models\TenantIdentity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantIdentity>
 */
class TenantIdentityFactory extends Factory
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
            'nik' => '3201'.fake()->numerify('############'),
            'nama_ktp' => fake()->name(),
            'foto_path' => null,
            'status' => TenantIdentity::MENUNGGU,
        ];
    }

    public function sah(): static
    {
        return $this->state(fn (array $attributes) => ['status' => TenantIdentity::SAH, 'verified_at' => now()]);
    }
}
