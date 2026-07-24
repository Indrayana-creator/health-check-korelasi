<?php

namespace Database\Factories;

use App\Models\HealthCheckForm;
use App\Models\Uker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthCheckForm>
 */
class HealthCheckFormFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uker_kode' => Uker::factory(),
            'pic_pn' => null,
            'tanggal_pemeriksaan' => fake()->date(),
            'periode' => fake()->randomElement(['Triwulan I', 'Triwulan II', 'Triwulan III', 'Triwulan IV']).' '.fake()->year(),
        ];
    }
}
