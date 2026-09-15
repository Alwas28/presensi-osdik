<?php

namespace Database\Factories;

use App\Models\Fakultas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fakultas>
 */
class FakultasFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'name' => 'Fakultas '.fake()->unique()->word(),
        ];
    }
}
