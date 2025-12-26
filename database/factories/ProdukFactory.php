<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Produk>
 */
class ProdukFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->words(2, true),
            'sku' => fake()->unique()->bothify('SKU-####-????'),
            'kategori_id' => 1,
            'harga_jual' => fake()->randomFloat(2, 10000, 100000),
            'harga_modal' => fake()->randomFloat(2, 5000, 50000),
            'deskripsi' => fake()->sentence(),
            'tipe' => fake()->randomElement(['beans', 'minuman', 'snack']),
            'satuan_dasar' => 'pcs',
            'aktif' => true,
            'perlu_kalibrasi' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}