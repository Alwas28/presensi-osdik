<?php

namespace Database\Seeders;

use App\Imports\MahasiswaImport;
use App\Models\Event;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class PkkmbDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database: real student data from data.xlsx,
     * the PKKMB 2026 event schedule, admin accounts, and demo attendance.
     */
    public function run(): void
    {
        $this->seedUsers();
        $mahasiswaIds = $this->importMahasiswa();
        $events = $this->seedEvents();
        $this->seedAttendance($mahasiswaIds, $events);
    }

    private function seedUsers(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'superadmin@umkendari.ac.id'],
            [
                'name' => 'Super Admin PKKMB',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'super_admin',
            ]
        );

        User::query()->firstOrCreate(
            ['email' => 'panitia@umkendari.ac.id'],
            [
                'name' => 'Panitia PKKMB',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'panitia',
            ]
        );
    }

    /**
     * @return list<int>
     */
    private function importMahasiswa(): array
    {
        $path = base_path('data.xlsx');

        if (! file_exists($path)) {
            $this->command?->warn('data.xlsx tidak ditemukan di root proyek, melewati import mahasiswa.');

            return Mahasiswa::query()->pluck('id')->all();
        }

        $importer = new MahasiswaImport;
        Excel::import($importer, $path);

        $this->command?->info(
            "Import mahasiswa dari data.xlsx: {$importer->created} baru, {$importer->updated} diperbarui, {$importer->skipped} dilewati."
        );

        return Mahasiswa::query()->pluck('id')->all();
    }

    /**
     * @return array<int, array{event: Event, hadirRate: float}>
     */
    private function seedEvents(): array
    {
        $definitions = [
            ['nama' => 'Pembukaan PKKMB 2026', 'tanggal' => '2026-09-15', 'jam_mulai' => '08:00', 'jam_selesai' => '09:00', 'lokasi' => 'Aula KH. Ahmad Dahlan', 'deskripsi' => 'Sesi pembukaan resmi PKKMB 2026.', 'status' => 'selesai', 'hadirRate' => 0.91],
            ['nama' => 'Pengenalan Universitas', 'tanggal' => '2026-09-15', 'jam_mulai' => '09:00', 'jam_selesai' => '10:00', 'lokasi' => 'Aula KH. Ahmad Dahlan', 'deskripsi' => 'Pengenalan profil, visi misi, dan sejarah UM Kendari.', 'status' => 'aktif', 'hadirRate' => 0.87],
            ['nama' => 'Pengenalan Fakultas', 'tanggal' => '2026-09-15', 'jam_mulai' => '10:00', 'jam_selesai' => '11:00', 'lokasi' => 'Gedung fakultas masing-masing', 'deskripsi' => 'Pengenalan pimpinan dan program kerja fakultas.', 'status' => 'belum_dibuka', 'hadirRate' => 0],
            ['nama' => 'Pengenalan Program Studi', 'tanggal' => '2026-09-15', 'jam_mulai' => '13:00', 'jam_selesai' => '15:00', 'lokasi' => 'Ruang prodi masing-masing', 'deskripsi' => 'Pengenalan kurikulum dan dosen program studi.', 'status' => 'belum_dibuka', 'hadirRate' => 0],
            ['nama' => 'Penutupan', 'tanggal' => '2026-09-16', 'jam_mulai' => '15:00', 'jam_selesai' => '16:00', 'lokasi' => 'Aula KH. Ahmad Dahlan', 'deskripsi' => 'Sesi penutupan rangkaian PKKMB 2026.', 'status' => 'belum_dibuka', 'hadirRate' => 0],
        ];

        $events = [];
        foreach ($definitions as $definition) {
            $hadirRate = $definition['hadirRate'];
            unset($definition['hadirRate']);
            $event = Event::query()->firstOrCreate(
                ['nama' => $definition['nama']],
                $definition
            );
            $events[] = ['event' => $event, 'hadirRate' => $hadirRate];
        }

        return $events;
    }

    /**
     * @param  list<int>  $mahasiswaIds
     * @param  array<int, array{event: Event, hadirRate: float}>  $events
     */
    private function seedAttendance(array $mahasiswaIds, array $events): void
    {
        if ($mahasiswaIds === []) {
            return;
        }

        foreach ($events as $entry) {
            $event = $entry['event'];
            $hadirRate = $entry['hadirRate'];

            if ($hadirRate <= 0 || $event->attendances()->exists()) {
                continue;
            }

            $rows = [];
            $startMinutes = $this->timeToMinutes($event->jam_mulai);
            $span = max(5, $this->timeToMinutes($event->jam_selesai) - $startMinutes);

            foreach ($mahasiswaIds as $mahasiswaId) {
                if ((random_int(0, 10000) / 10000) >= $hadirRate) {
                    continue;
                }

                $checkIn = $event->tanggal->copy()->startOfDay()
                    ->addMinutes($startMinutes + random_int(0, $span - 1))
                    ->addSeconds(random_int(0, 59));

                $rows[] = [
                    'mahasiswa_id' => $mahasiswaId,
                    'event_id' => $event->id,
                    'attendance_date' => $event->tanggal->format('Y-m-d'),
                    'check_in' => $checkIn,
                    'status' => 'hadir',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            collect($rows)->chunk(500)->each(function ($chunk) {
                DB::table('attendances')->insert($chunk->all());
            });
        }
    }

    private function timeToMinutes(mixed $time): int
    {
        $value = $time instanceof \DateTimeInterface ? $time->format('H:i') : (string) $time;
        [$hours, $minutes] = array_map('intval', explode(':', $value));

        return $hours * 60 + $minutes;
    }
}
