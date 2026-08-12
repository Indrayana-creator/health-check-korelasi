<?php

namespace Database\Factories;

use App\Models\PermintaanPerangkat;
use App\Models\Uker;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PermintaanPerangkat>
 */
class PermintaanPerangkatFactory extends Factory
{
    public function definition(): array
    {
        return [
            'no_nota_dinas' => 'ND-'.fake()->unique()->numerify('####/VIII/2026'),
            'tanggal_request' => now(),
            'fungsi_requester' => fake()->randomElement(['RSF', 'MRR', 'CS', 'Teller']),
            'jumlah' => fake()->numberBetween(1, 5),
            'keterangan' => fake()->sentence(),
            'status' => 'Pending IT',
            'uker_kode' => Uker::factory(),
            'requested_by' => User::factory(),
        ];
    }
}
