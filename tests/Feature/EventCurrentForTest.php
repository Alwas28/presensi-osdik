<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Fakultas;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for the "presensi/mahasiswa tidak menampilkan kegiatan
 * yang terbuka dan belum pernah presensi" report: when more than one event is
 * aktif at once, the student must land on the one they still need to attend,
 * not just whichever aktif row happens to come back first.
 */
class EventCurrentForTest extends TestCase
{
    use RefreshDatabase;

    private function makeMahasiswa(): Mahasiswa
    {
        $prodi = ProgramStudi::factory()->for(Fakultas::factory())->create();

        return Mahasiswa::factory()->for($prodi, 'programStudi')->create();
    }

    public function test_it_returns_the_only_active_event_when_unattended(): void
    {
        $mahasiswa = $this->makeMahasiswa();
        $event = Event::factory()->create(['status' => 'aktif']);

        $this->assertTrue(Event::currentFor($mahasiswa)->is($event));
    }

    public function test_it_skips_an_already_attended_active_event_in_favor_of_another(): void
    {
        $mahasiswa = $this->makeMahasiswa();
        $attended = Event::factory()->create(['status' => 'aktif']);
        $notYetAttended = Event::factory()->create(['status' => 'aktif']);

        Attendance::factory()->for($mahasiswa, 'mahasiswa')->for($attended)->create();

        $this->assertTrue(Event::currentFor($mahasiswa)->is($notYetAttended));
    }

    public function test_it_falls_back_to_an_already_attended_event_when_all_active_ones_are_done(): void
    {
        $mahasiswa = $this->makeMahasiswa();
        $attended = Event::factory()->create(['status' => 'aktif']);

        Attendance::factory()->for($mahasiswa, 'mahasiswa')->for($attended)->create();

        $this->assertTrue(Event::currentFor($mahasiswa)->is($attended));
    }

    public function test_it_returns_null_when_no_event_is_active(): void
    {
        $mahasiswa = $this->makeMahasiswa();
        Event::factory()->create(['status' => 'selesai']);

        $this->assertNull(Event::currentFor($mahasiswa));
    }
}
