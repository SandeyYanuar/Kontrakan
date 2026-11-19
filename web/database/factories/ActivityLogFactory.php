<?php

namespace Database\Factories;

use App\Enums\Aksi;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_id' => User::factory()->superAdmin(),
            'aksi' => Aksi::PengaturanDiubah,
            'data' => null,
        ];
    }
}
