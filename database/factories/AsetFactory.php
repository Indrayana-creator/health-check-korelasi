<?php

namespace Database\Factories;

use App\Models\Aset;
use App\Models\KodeAset;
use App\Models\Uker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Aset>
 */
class AsetFactory extends Factory
{
    public function definition(): array
    {
        $ukerKode = Uker::factory();
        $kodeAsetKode = KodeAset::factory();

        return [
            'no_asset' => 'Z5-K-'.fake()->unique()->numerify('####').'-TEST-0001',
            'uker_kode' => $ukerKode,
            'kode_aset_kode' => $kodeAsetKode,
            'merek' => fake()->randomElement(['Dell', 'HP', 'Lenovo', 'Acer']),
            'tipe_model' => fake()->bothify('?????-####'),
            'sn' => fake()->unique()->bothify('SN########'),
            'kapasitas_memori' => fake()->randomElement(['8GB', '16GB', '32GB']),
            'tahun_perolehan' => fake()->numberBetween(2018, (int) date('Y')),
            'kondisi' => fake()->randomElement(Aset::DAFTAR_KONDISI),
            'pemegang_nama' => fake()->name(),
            'jabatan' => fake()->jobTitle(),
            'pemegang_pn' => null,
            'ip_address' => fake()->localIpv4(),
            'status_hardening' => 'Sudah',
            'status_bitlocker' => 'Aktif',
            'status_dlp' => 'Aktif',
            'status_antivirus' => 'Aktif',
            'keterangan' => null,
        ];
    }
}
