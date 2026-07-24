<?php

namespace Database\Factories;

use App\Models\KodeAset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KodeAset>
 */
class KodeAsetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode' => 'K'.fake()->unique()->numberBetween(1, 9999),
            'kategori' => fake()->randomElement(['Komputer', 'Jaringan', 'Peripheral']),
            'nama' => fake()->randomElement(['Laptop Standar', 'PC Standar', 'Printer', 'Switch', 'Monitor']),
        ];
    }
}
