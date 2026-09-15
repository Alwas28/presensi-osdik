<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Fakultas;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_totals_for_the_active_event(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $fakultas = Fakultas::factory()->create(['code' => 'FT', 'name' => 'Fakultas Teknik']);
        $prodi = ProgramStudi::factory()->for($fakultas)->create();
        $mahasiswas = Mahasiswa::factory()->for($prodi, 'programStudi')->count(5)->create();
        $event = Event::factory()->create(['status' => 'aktif']);

        Attendance::factory()->for($mahasiswas[0], 'mahasiswa')->for($event)->create();
        Attendance::factory()->for($mahasiswas[1], 'mahasiswa')->for($event)->create();

        Volt::actingAs($admin)
            ->test('pages.admin.dashboard')
            ->assertSee('5') // total mahasiswa
            ->assertSee('2') // hadir
            ->assertSee('40.0%'); // 2/5 = 40%
    }

    public function test_opening_qr_dispatches_the_qr_open_event(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $event = Event::factory()->create(['status' => 'aktif']);

        Volt::actingAs($admin)
            ->test('pages.admin.dashboard')
            ->call('openQr', $event->id)
            ->assertDispatched('qr-open', eventId: $event->id);
    }
}
