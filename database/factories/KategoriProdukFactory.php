<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KategoriProduk>
 */
class KategoriProdukFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nama = fake()->word();
        
        return [
            'nama' => $nama,
            'slug' => Str::slug($nama),
            'deskripsi' => fake()->sentence(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}