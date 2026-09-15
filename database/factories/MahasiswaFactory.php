<?php

namespace Database\Factories;

use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mahasiswa>
 */
class MahasiswaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $angkatan = 2026;

        return [
            'no_registrasi' => 'REG'.fake()->unique()->numerify('########'),
            'nim' => (string) fake()->unique()->numerify($angkatan.'##########'),
            'nama' => fake()->name(),
            'no_telp' => fake()->numerify('08##########'),
            'status_pendaftar' => fake()->randomElement(['Diterima', 'Registrasi Ulang']),
            'jalur' => fake()->randomElement(['SNBP', 'SNBT', 'Mandiri']),
            'program_studi_id' => ProgramStudi::factory(),
            'angkatan' => $angkatan,
            'email' => fake()->unique()->safeEmail(),
        ];
    }
}
