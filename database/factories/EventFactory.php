<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->sentence(3),
            'tanggal' => fake()->dateTimeBetween('now', '+2 days')->format('Y-m-d'),
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:00',
            'lokasi' => fake()->streetAddress(),
            'deskripsi' => fake()->sentence(),
            'status' => 'draft',
            'metode_presensi' => 'qr',
        ];
    }
}
