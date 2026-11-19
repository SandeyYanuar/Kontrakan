<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'user_id' => fn (array $attributes) => Invoice::find($attributes['invoice_id'])->lease->user_id,
            'file_path' => 'bukti/contoh.jpg',
            'nominal_transfer' => fn (array $attributes) => Invoice::find($attributes['invoice_id'])->total,
            'tgl_transfer' => now()->toDateString(),
            'status' => Payment::MENUNGGU,
        ];
    }

    public function ditolak(string $alasan = 'Nominal kurang'): static
    {
        return $this->state(fn (array $attributes) => ['status' => Payment::DITOLAK, 'alasan' => $alasan, 'reviewed_at' => now()]);
    }

    public function disetujui(): static
    {
        return $this->state(fn (array $attributes) => ['status' => Payment::DISETUJUI, 'reviewed_at' => now()]);
    }
}
