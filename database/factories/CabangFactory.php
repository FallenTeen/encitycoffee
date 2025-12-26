<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cabang>
 */
class CabangFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->bothify('CAB-####'),
            'nama' => fake()->company(),
            'alamat' => fake()->address(),
            'telepon' => fake()->phoneNumber(),
            'aktif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}