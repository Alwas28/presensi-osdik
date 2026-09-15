<?php

namespace Database\Seeders;

use App\Models\Aduan;
use App\Models\Attendance;
use App\Models\Event;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Wipes trial/testing data built up while developing and demoing the app —
 * kegiatan, presensi, and aduan — without touching mahasiswa/user accounts or
 * the fakultas/program studi reference data.
 *
 * Deliberately NOT called from DatabaseSeeder::run(): this is a destructive,
 * one-off cleanup, run explicitly with:
 *
 *     php artisan db:seed --class=PurgeTrialDataSeeder
 */
class PurgeTrialDataSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Delete attendances before events even though the FK already cascades,
        // so this stays correct if that constraint ever changes.
        Attendance::query()->delete();
        Event::query()->delete();
        Aduan::query()->delete();

        $this->command?->info('Data percobaan (kegiatan, presensi, aduan) sudah dihapus.');
    }
}
