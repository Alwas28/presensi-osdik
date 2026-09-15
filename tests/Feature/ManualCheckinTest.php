<?php

namespace Tests\Feature;

use App\Exceptions\PresensiException;
use App\Models\Event;
use App\Models\Fakultas;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\User;
use App\Support\AttendanceRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ManualCheckinTest extends TestCase
{
    use RefreshDatabase;

    private function makeMahasiswa(): Mahasiswa
    {
        $prodi = ProgramStudi::factory()->for(Fakultas::factory())->create();

        return Mahasiswa::factory()->for($prodi, 'programStudi')->create();
    }

    public function test_it_records_manual_checkin_when_event_allows_it(): void
    {
        $mahasiswa = $this->makeMahasiswa();
        $event = Event::factory()->create(['status' => 'aktif', 'izinkan_presensi_manual' => true]);

        $attendance = (new AttendanceRecorder)->recordManualCheckin($mahasiswa, $event);

        $this->assertSame($mahasiswa->id, $attendance->mahasiswa_id);
        $this->assertSame('manual', $attendance->channel);
    }

    public function test_it_rejects_manual_checkin_when_event_does_not_allow_it(): void
    {
        $mahasiswa = $this->makeMahasiswa();
        $event = Event::factory()->create(['status' => 'aktif', 'izinkan_presensi_manual' => false]);

        $this->expectException(PresensiException::class);
        $this->expectExceptionMessage('Presensi manual belum diaktifkan panitia untuk kegiatan ini.');

        (new AttendanceRecorder)->recordManualCheckin($mahasiswa, $event);
    }

    public function test_it_rejects_manual_checkin_when_event_is_not_active(): void
    {
        $mahasiswa = $this->makeMahasiswa();
        $event = Event::factory()->create(['status' => 'ditutup', 'izinkan_presensi_manual' => true]);

        $this->expectException(PresensiException::class);

        (new AttendanceRecorder)->recordManualCheckin($mahasiswa, $event);
    }

    public function test_it_rejects_a_duplicate_manual_checkin(): void
    {
        $mahasiswa = $this->makeMahasiswa();
        $event = Event::factory()->create(['status' => 'aktif', 'izinkan_presensi_manual' => true]);

        $recorder = new AttendanceRecorder;
        $recorder->recordManualCheckin($mahasiswa, $event);

        $this->expectException(PresensiException::class);
        $this->expectExceptionMessage('Kamu sudah presensi untuk kegiatan ini.');

        $recorder->recordManualCheckin($mahasiswa, $event);
    }

    public function test_manual_checkin_button_works_from_the_presensi_page(): void
    {
        $prodi = ProgramStudi::factory()->for(Fakultas::factory())->create();
        $mahasiswa = Mahasiswa::factory()->for($prodi, 'programStudi')->create();
        $user = User::factory()->create(['mahasiswa_id' => $mahasiswa->id, 'role' => 'mahasiswa']);
        Event::factory()->create(['status' => 'aktif', 'izinkan_presensi_manual' => true]);

        Volt::actingAs($user)
            ->test('pages.mahasiswa.presensi')
            ->call('manualCheckin')
            ->assertSet('feedbackType', 'success')
            ->assertDispatched('presensi-berhasil');
    }

    public function test_manual_checkin_button_is_visible_without_a_prior_failed_scan(): void
    {
        $prodi = ProgramStudi::factory()->for(Fakultas::factory())->create();
        $mahasiswa = Mahasiswa::factory()->for($prodi, 'programStudi')->create();
        $user = User::factory()->create(['mahasiswa_id' => $mahasiswa->id, 'role' => 'mahasiswa']);
        Event::factory()->create(['status' => 'aktif', 'izinkan_presensi_manual' => true]);

        Volt::actingAs($user)
            ->test('pages.mahasiswa.presensi')
            ->assertSet('feedback', null)
            ->assertSee('Presensi Manual');
    }

    public function test_manual_checkin_button_is_hidden_when_the_event_does_not_allow_it(): void
    {
        $prodi = ProgramStudi::factory()->for(Fakultas::factory())->create();
        $mahasiswa = Mahasiswa::factory()->for($prodi, 'programStudi')->create();
        $user = User::factory()->create(['mahasiswa_id' => $mahasiswa->id, 'role' => 'mahasiswa']);
        Event::factory()->create(['status' => 'aktif', 'izinkan_presensi_manual' => false]);

        Volt::actingAs($user)
            ->test('pages.mahasiswa.presensi')
            ->assertDontSee('Presensi Manual');
    }

    public function test_only_super_admin_can_toggle_manual_checkin_for_an_event(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $event = Event::factory()->create(['izinkan_presensi_manual' => false]);

        Volt::actingAs($panitia)
            ->test('pages.admin.kegiatan')
            ->call('toggleManualCheckin', $event->id)
            ->assertForbidden();

        $this->assertFalse($event->fresh()->izinkan_presensi_manual);
    }

    public function test_super_admin_can_toggle_manual_checkin_for_an_event(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $event = Event::factory()->create(['izinkan_presensi_manual' => false]);

        Volt::actingAs($admin)
            ->test('pages.admin.kegiatan')
            ->call('toggleManualCheckin', $event->id);

        $this->assertTrue($event->fresh()->izinkan_presensi_manual);
    }
}
