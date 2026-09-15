<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Fakultas;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\User;
use App\Support\AttendanceRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * Covers the browser toast wiring itself (resources/views/components/success-toast.blade.php
 * listens via `livewire:init`/`window.Livewire`, not something PHPUnit can render), but pins
 * down the one thing that actually broke: each successful check-in path must dispatch its
 * named event, or the toast has nothing to react to.
 */
class PresensiSuccessAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_mahasiswa_checkin_dispatches_the_success_event(): void
    {
        $prodi = ProgramStudi::factory()->for(Fakultas::factory())->create();
        $mahasiswa = Mahasiswa::factory()->for($prodi, 'programStudi')->create();
        $user = User::factory()->create(['mahasiswa_id' => $mahasiswa->id, 'role' => 'mahasiswa']);
        $event = Event::factory()->create(['status' => 'aktif']);
        $token = $event->rotateToken();

        Volt::actingAs($user)
            ->test('pages.mahasiswa.presensi')
            ->call('checkinWithPayload', "EVT:{$event->id}:{$token}")
            ->assertDispatched('presensi-berhasil');
    }

    public function test_mahasiswa_checkin_does_not_dispatch_on_failure(): void
    {
        $prodi = ProgramStudi::factory()->for(Fakultas::factory())->create();
        $mahasiswa = Mahasiswa::factory()->for($prodi, 'programStudi')->create();
        $user = User::factory()->create(['mahasiswa_id' => $mahasiswa->id, 'role' => 'mahasiswa']);
        Event::factory()->create(['status' => 'aktif']);

        Volt::actingAs($user)
            ->test('pages.mahasiswa.presensi')
            ->call('checkinWithPayload', 'EVT:999:invalid-token')
            ->assertNotDispatched('presensi-berhasil');
    }

    public function test_admin_scan_dispatches_the_success_event(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $prodi = ProgramStudi::factory()->for(Fakultas::factory())->create();
        $mahasiswa = Mahasiswa::factory()->for($prodi, 'programStudi')->create();
        $event = Event::factory()->create(['status' => 'aktif']);
        $payload = app(AttendanceRecorder::class)->buildSelfQrPayload($mahasiswa, $event);

        Volt::actingAs($admin)
            ->test('pages.admin.scan-presensi')
            ->call('scan', $payload)
            ->assertDispatched('presensi-panitia-berhasil');
    }

    public function test_admin_scan_does_not_dispatch_on_failure(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        Volt::actingAs($admin)
            ->test('pages.admin.scan-presensi')
            ->call('scan', 'SELF:not-a-valid-payload')
            ->assertNotDispatched('presensi-panitia-berhasil');
    }
}
