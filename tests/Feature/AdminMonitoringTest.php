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

class AdminMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_filtering_by_fakultas_only_counts_that_fakultas(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $event = Event::factory()->create();

        $teknik = Fakultas::factory()->create(['name' => 'Fakultas Teknik']);
        $prodiTeknik = ProgramStudi::factory()->for($teknik)->create();
        $mahasiswaTeknik = Mahasiswa::factory()->for($prodiTeknik, 'programStudi')->count(3)->create();
        Attendance::factory()->for($mahasiswaTeknik[0], 'mahasiswa')->for($event)->create();

        $hukum = Fakultas::factory()->create(['name' => 'Fakultas Hukum']);
        $prodiHukum = ProgramStudi::factory()->for($hukum)->create();
        Mahasiswa::factory()->for($prodiHukum, 'programStudi')->count(2)->create();

        Volt::actingAs($admin)
            ->test('pages.admin.monitoring')
            ->set('eventId', $event->id)
            ->set('fakultas', $teknik->id)
            ->assertSet('totalCount', 3)
            ->assertSet('hadirCount', 1);
    }

    public function test_search_narrows_the_log_by_name_or_nim(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $event = Event::factory()->create();
        $prodi = ProgramStudi::factory()->create();

        $target = Mahasiswa::factory()->for($prodi, 'programStudi')->create(['nama' => 'Ahmad Fauzan', 'nim' => '20260001']);
        $other = Mahasiswa::factory()->for($prodi, 'programStudi')->create(['nama' => 'Siti Rahma', 'nim' => '20260002']);
        Attendance::factory()->for($target, 'mahasiswa')->for($event)->create();
        Attendance::factory()->for($other, 'mahasiswa')->for($event)->create();

        Volt::actingAs($admin)
            ->test('pages.admin.monitoring')
            ->set('eventId', $event->id)
            ->set('search', 'Fauzan')
            ->assertSee('Ahmad Fauzan')
            ->assertDontSee('Siti Rahma');
    }
}
