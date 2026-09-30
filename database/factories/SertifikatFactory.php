<?php

namespace Database\Factories;

use App\Models\Fakultas;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\Sertifikat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sertifikat>
 */
class SertifikatFactory extends Factory
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
            'nomor' => $this->faker->unique()->numerify('###/SERTIFIKAT/OSDIK/UMK/IX/2026'),
        ];
    }
}
