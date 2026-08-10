<?php

namespace Database\Factories;

use App\Models\Pekerja;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            // Login sekarang pakai PN, bukan email -- FK users.pn -> pekerja.pn
            // butuh baris pekerja yang valid, jadi tiap User factory otomatis
            // bikin 1 Pekerja dummy buat dipasangin.
            'pn' => Pekerja::factory(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'uker_kode' => null,
        ]);
    }

    public function forUker(int $ukerKode): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'user',
            'uker_kode' => $ukerKode,
        ]);
    }
}
