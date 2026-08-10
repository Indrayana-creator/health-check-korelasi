<?php

namespace Database\Factories;

use App\Models\Pekerja;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pekerja>
 */
class PekerjaFactory extends Factory
{
    protected $model = Pekerja::class;

    public function definition(): array
    {
        return [
            'pn' => fake()->unique()->numerify('#######'),
            'nama' => fake()->name(),
            'jabatan' => fake()->jobTitle(),
            'status' => 'Aktif',
            'uker_kode' => null,
            'is_petugas_it' => false,
            'no_hp' => fake()->numerify('08##########'),
        ];
    }
}
