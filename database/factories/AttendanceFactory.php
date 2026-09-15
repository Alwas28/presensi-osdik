<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Mahasiswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkIn = fake()->dateTimeBetween('-1 hour', 'now');

        return [
            'mahasiswa_id' => Mahasiswa::factory(),
            'event_id' => Event::factory(),
            'attendance_date' => $checkIn->format('Y-m-d'),
            'check_in' => $checkIn,
            'status' => 'hadir',
        ];
    }
}
