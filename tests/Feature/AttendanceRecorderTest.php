<?php

namespace Tests\Feature;

use App\Exceptions\PresensiException;
use App\Models\Event;
use App\Models\Fakultas;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Support\AttendanceRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AttendanceRecorderTest extends TestCase
{
    use RefreshDatabase;

    private function makeMahasiswa(): Mahasiswa
    {
        $prodi = ProgramStudi::factory()->for(Fakultas::factory())->create();

        return Mahasiswa::factory()->for($prodi, 'programStudi')->create();
    }

    public function test_it_records_attendance_from_a_valid_event_qr(): void
    {
        $mahasiswa = $this->makeMahasiswa();
        $event = Event::factory()->create(['status' => 'aktif']);
        $token = $event->rotateToken();

        $attendance = (new AttendanceRecorder)->recordFromEventQr($mahasiswa, "EVT:{$event->id}:{$token}");

        $this->assertSame($mahasiswa->id, $attendance->mahasiswa_id);
        $this->assertSame($event->id, $attendance->event_id);
        $this->assertSame('hadir', $attendance->status);
    }

    public function test_it_accepts_a_bare_token_with_the_selected_event_id(): void
    {
        $mahasiswa = $this->makeMahasiswa();
        $event = Event::factory()->create(['status' => 'aktif']);
        $token = $event->rotateToken();

        $attendance = (new AttendanceRecorder)->recordFromEventQr($mahasiswa, $token, $event->id);

        $this->assertSame($event->id, $attendance->event_id);
    }

    public function test_it_rejects_an_invalid_token(): void
    {
        $mahasiswa = $this->makeMahasiswa();
        $event = Event::factory()->create(['status' => 'aktif']);
        $event->rotateToken();

        $this->expectException(PresensiException::class);
        $this->expectExceptionMessage('Token QR tidak valid atau sudah kedaluwarsa, coba lagi.');

        (new AttendanceRecorder)->recordFromEventQr($mahasiswa, "EVT:{$event->id}:salah-token");
    }

    public function test_it_rejects_when_event_is_not_active(): void
    {
        $mahasiswa = $this->makeMahasiswa();
        $event = Event::factory()->create(['status' => 'belum_dibuka']);
        $token = $event->rotateToken();

        $this->expectException(PresensiException::class);
        $this->expectExceptionMessage('Kegiatan belum dibuka atau sudah ditutup untuk presensi.');

        (new AttendanceRecorder)->recordFromEventQr($mahasiswa, "EVT:{$event->id}:{$token}");
    }

    public function test_it_rejects_a_duplicate_attendance(): void
    {
        $mahasiswa = $this->makeMahasiswa();
        $event = Event::factory()->create(['status' => 'aktif']);
        $token = $event->rotateToken();

        $recorder = new AttendanceRecorder;
        $recorder->recordFromEventQr($mahasiswa, "EVT:{$event->id}:{$token}");

        $this->expectException(PresensiException::class);
        $this->expectExceptionMessage('Kamu sudah presensi untuk kegiatan ini.');

        $recorder->recordFromEventQr($mahasiswa, "EVT:{$event->id}:{$token}");
    }

    public function test_it_records_attendance_from_a_valid_self_qr(): void
    {
        $mahasiswa = $this->makeMahasiswa();
        $event = Event::factory()->create(['status' => 'aktif']);

        $recorder = new AttendanceRecorder;
        $payload = $recorder->buildSelfQrPayload($mahasiswa, $event);

        $attendance = $recorder->recordFromSelfQr($payload);

        $this->assertSame($mahasiswa->id, $attendance->mahasiswa_id);
        $this->assertSame($event->id, $attendance->event_id);
    }

    public function test_it_rejects_an_expired_self_qr(): void
    {
        $mahasiswa = $this->makeMahasiswa();
        $event = Event::factory()->create(['status' => 'aktif']);

        // Store it already-expired instead of waiting out the real TTL.
        Cache::put('self-qr:expired-token', [
            'mahasiswa_id' => $mahasiswa->id,
            'event_id' => $event->id,
        ], now()->subSecond());

        $this->expectException(PresensiException::class);
        $this->expectExceptionMessage('QR sudah kedaluwarsa atau tidak valid, minta mahasiswa membuka ulang halaman presensi.');

        (new AttendanceRecorder)->recordFromSelfQr('SELF:expired-token');
    }

    public function test_a_self_qr_cannot_be_reused_once_scanned(): void
    {
        $mahasiswa = $this->makeMahasiswa();
        $event = Event::factory()->create(['status' => 'aktif']);

        $recorder = new AttendanceRecorder;
        $payload = $recorder->buildSelfQrPayload($mahasiswa, $event);

        $recorder->recordFromSelfQr($payload);

        $this->expectException(PresensiException::class);
        $this->expectExceptionMessage('QR sudah kedaluwarsa atau tidak valid, minta mahasiswa membuka ulang halaman presensi.');

        $recorder->recordFromSelfQr($payload);
    }

    public function test_it_rejects_a_self_qr_for_an_inactive_event(): void
    {
        $mahasiswa = $this->makeMahasiswa();
        $event = Event::factory()->create(['status' => 'selesai']);

        $recorder = new AttendanceRecorder;
        $payload = $recorder->buildSelfQrPayload($mahasiswa, $event);

        $this->expectException(PresensiException::class);
        $this->expectExceptionMessage('Kegiatan belum dibuka atau sudah ditutup untuk presensi.');

        $recorder->recordFromSelfQr($payload);
    }

    public function test_it_rejects_a_malformed_self_qr(): void
    {
        $this->expectException(PresensiException::class);

        (new AttendanceRecorder)->recordFromSelfQr('SELF:not-encrypted-data');
    }
}
