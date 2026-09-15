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
            // Uker::factory() doang bakal resolve ke id (PK default Eloquent),
            // padahal FK ini nunjuk ke kolom kode -- harus dituntun eksplisit.
            'uker_kode' => Uker::factory()->create()->kode,
            'pic_pn' => null,
            'tanggal_pemeriksaan' => fake()->date(),
            'periode' => fake()->randomElement(['Triwulan I', 'Triwulan II', 'Triwulan III', 'Triwulan IV']).' '.fake()->year(),
        ];
    }
}
