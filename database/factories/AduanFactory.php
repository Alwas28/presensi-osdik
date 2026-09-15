<?php

namespace Database\Factories;

use App\Models\Aduan;
use App\Models\Fakultas;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Aduan>
 */
class AduanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mahasiswa_id' => Mahasiswa::factory()->for(ProgramStudi::factory()->for(Fakultas::factory()), 'programStudi'),
            'judul' => $this->faker->sentence(4),
            'pesan' => $this->faker->paragraph(),
        ];
    }
}
