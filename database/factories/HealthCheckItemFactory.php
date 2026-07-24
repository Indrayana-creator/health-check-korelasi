<?php

namespace Database\Factories;

use App\Models\HealthCheckForm;
use App\Models\HealthCheckItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthCheckItem>
 */
class HealthCheckItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'health_check_form_id' => HealthCheckForm::factory(),
            'kategori' => fake()->randomElement([
                'A - Ruang Server/Jaringan',
                'B - CCTV & Storage',
                'C - Jaringan',
                'D - Power System (UPS)',
                'E - Dokumentasi Visual',
            ]),
            'item_pemeriksaan' => fake()->sentence(),
            'status' => 'Belum Diperiksa',
            'catatan' => null,
        ];
    }
}
