<?php

namespace Database\Factories;

use App\Models\Uker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Uker>
 */
class UkerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->numberBetween(1, 9998),
            'nama' => 'KC '.fake()->city(),
            'jenis' => fake()->randomElement(['Kanwil', 'Kanca Konsol', 'Unit']),
            'kode_spv' => null,
            'uker_spv' => fake()->city(),
        ];
    }
}
