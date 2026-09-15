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

class AdminLaporanTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_recaps_attendance_per_fakultas(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $event = Event::factory()->create();

        $fakultas = Fakultas::factory()->create(['name' => 'Fakultas Teknik']);
        $prodi = ProgramStudi::factory()->for($fakultas)->create(['name' => 'Teknik Sipil']);
        $mahasiswas = Mahasiswa::factory()->for($prodi, 'programStudi')->count(4)->create();
        Attendance::factory()->for($mahasiswas[0], 'mahasiswa')->for($event)->create();
        Attendance::factory()->for($mahasiswas[1], 'mahasiswa')->for($event)->create();

        $component = Volt::actingAs($admin)
            ->test('pages.admin.laporan')
            ->set('eventId', $event->id);

        $rekap = $component->get('rekap');
        $teknikRow = collect($rekap)->firstWhere('fakultas', 'Fakultas Teknik');

        $this->assertSame(4, $teknikRow['total']);
        $this->assertSame(2, $teknikRow['hadir']);
        $this->assertSame(2, $teknikRow['belum']);
    }

    public function test_export_rows_include_a_header_and_one_row_per_prodi(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $event = Event::factory()->create();
        $fakultas = Fakultas::factory()->create();
        ProgramStudi::factory()->for($fakultas)->create(['name' => 'Teknik Sipil']);

        $rows = Volt::actingAs($admin)
            ->test('pages.admin.laporan')
            ->set('eventId', $event->id)
            ->get('exportRows');

        $this->assertSame(['Fakultas', 'Program Studi', 'Peserta', 'Hadir', 'Belum', 'Persentase'], $rows[0]);
        $this->assertContains('Teknik Sipil', array_column($rows, 1));
    }
}
