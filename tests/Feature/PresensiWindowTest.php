<?php

namespace Tests\Feature;

use App\Exceptions\PresensiException;
use App\Models\Event;
use App\Models\Fakultas;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Support\AttendanceRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * "buatkan waktu aktif presensi misal dari jam 1 sampai jam 3" — an event can
 * optionally restrict presensi to a time window on top of being aktif.
 */
class PresensiWindowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeMahasiswa(): Mahasiswa
    {
        $prodi = ProgramStudi::factory()->for(Fakultas::factory())->create();

        return Mahasiswa::factory()->for($prodi, 'programStudi')->create();
    }

    public function test_it_allows_checkin_inside_the_presensi_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-15 13:30:00'));

        $mahasiswa = $this->makeMahasiswa();
        $event = Event::factory()->create([
            'status' => 'aktif',
            'tanggal' => '2026-09-15',
            'jam_mulai_presensi' => '13:00',
            'jam_selesai_presensi' => '15:00',
        ]);
        $token = $event->rotateToken();

        $attendance = (new AttendanceRecorder)->recordFromEventQr($mahasiswa, "EVT:{$event->id}:{$token}");

        $this->assertSame($mahasiswa->id, $attendance->mahasiswa_id);
    }

    public function test_it_rejects_checkin_before_the_presensi_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-15 12:30:00'));

        $mahasiswa = $this->makeMahasiswa();
        $event = Event::factory()->create([
            'status' => 'aktif',
            'tanggal' => '2026-09-15',
            'jam_mulai_presensi' => '13:00',
            'jam_selesai_presensi' => '15:00',
        ]);
        $token = $event->rotateToken();

        $this->expectException(PresensiException::class);
        $this->expectExceptionMessage('Presensi hanya bisa dilakukan pukul 13:00 - 15:00.');

        (new AttendanceRecorder)->recordFromEventQr($mahasiswa, "EVT:{$event->id}:{$token}");
    }

    public function test_it_rejects_checkin_after_the_presensi_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-15 15:30:00'));

        $mahasiswa = $this->makeMahasiswa();
        $event = Event::factory()->create([
            'status' => 'aktif',
            'tanggal' => '2026-09-15',
            'jam_mulai_presensi' => '13:00',
            'jam_selesai_presensi' => '15:00',
        ]);
        $token = $event->rotateToken();

        $this->expectException(PresensiException::class);

        (new AttendanceRecorder)->recordFromEventQr($mahasiswa, "EVT:{$event->id}:{$token}");
    }

    public function test_it_allows_checkin_at_any_time_when_no_window_is_set(): void
    {
        $mahasiswa = $this->makeMahasiswa();
        $event = Event::factory()->create([
            'status' => 'aktif',
            'jam_mulai_presensi' => null,
            'jam_selesai_presensi' => null,
        ]);
        $token = $event->rotateToken();

        $attendance = (new AttendanceRecorder)->recordFromEventQr($mahasiswa, "EVT:{$event->id}:{$token}");

        $this->assertSame($mahasiswa->id, $attendance->mahasiswa_id);
    }
}
