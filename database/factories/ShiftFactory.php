<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shift>
 */
class ShiftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cabang_id' => 1,
            'user_id' => 1,
            'saldo_awal' => fake()->randomFloat(2, 100000, 1000000),
            'saldo_akhir' => fake()->randomFloat(2, 100000, 1000000),
            'total_tunai' => fake()->randomFloat(2, 0, 500000),
            'total_qris' => fake()->randomFloat(2, 0, 500000),
            'waktu_buka' => now(),
            'waktu_tutup' => null,
            'status' => 'aktif',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}