<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AdminKegiatanEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_edit_an_existing_event(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $event = Event::factory()->create([
            'nama' => 'Nama Lama',
            'lokasi' => 'Lokasi Lama',
        ]);

        Volt::actingAs($admin)
            ->test('pages.admin.kegiatan')
            ->call('openEditModal', $event->id)
            ->assertSet('nama', 'Nama Lama')
            ->set('nama', 'Nama Baru')
            ->set('lokasi', 'Lokasi Baru')
            ->set('jamMulaiPresensi', '13:00')
            ->set('jamSelesaiPresensi', '15:00')
            ->call('save')
            ->assertHasNoErrors();

        $event->refresh();
        $this->assertSame('Nama Baru', $event->nama);
        $this->assertSame('Lokasi Baru', $event->lokasi);
        $this->assertSame('13:00', substr($event->jam_mulai_presensi, 0, 5));
        $this->assertSame('15:00', substr($event->jam_selesai_presensi, 0, 5));
    }

    public function test_editing_does_not_create_a_duplicate_event(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $event = Event::factory()->create();

        Volt::actingAs($admin)
            ->test('pages.admin.kegiatan')
            ->call('openEditModal', $event->id)
            ->set('nama', 'Diubah')
            ->call('save');

        $this->assertSame(1, Event::query()->count());
    }

    public function test_presensi_end_time_must_be_after_start_time(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $event = Event::factory()->create();

        Volt::actingAs($admin)
            ->test('pages.admin.kegiatan')
            ->call('openEditModal', $event->id)
            ->set('jamMulaiPresensi', '15:00')
            ->set('jamSelesaiPresensi', '13:00')
            ->call('save')
            ->assertHasErrors(['jamSelesaiPresensi']);

        $this->assertNull($event->fresh()->jam_mulai_presensi);
    }
}
