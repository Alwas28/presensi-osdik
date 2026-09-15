<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AdminKegiatanTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_draft_event(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        Volt::actingAs($admin)
            ->test('pages.admin.kegiatan')
            ->set('nama', 'Pengenalan Fakultas')
            ->set('tanggal', '2026-09-15')
            ->set('jamMulai', '10:00')
            ->set('jamSelesai', '11:00')
            ->set('lokasi', 'Gedung fakultas masing-masing')
            ->call('save')
            ->assertSet('showModal', false);

        $this->assertDatabaseHas('events', [
            'nama' => 'Pengenalan Fakultas',
            'status' => 'draft',
        ]);
    }

    public function test_creating_an_event_requires_required_fields(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        Volt::actingAs($admin)
            ->test('pages.admin.kegiatan')
            ->set('nama', '')
            ->call('save')
            ->assertHasErrors(['nama' => 'required']);
    }

    public function test_admin_can_toggle_event_status(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $event = Event::factory()->create(['status' => 'belum_dibuka']);

        Volt::actingAs($admin)
            ->test('pages.admin.kegiatan')
            ->call('toggleStatus', $event->id);

        $this->assertSame('aktif', $event->fresh()->status);
    }

    public function test_opening_one_event_closes_any_other_active_event(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $alreadyOpen = Event::factory()->create(['status' => 'aktif']);
        $toOpen = Event::factory()->create(['status' => 'belum_dibuka']);

        Volt::actingAs($admin)
            ->test('pages.admin.kegiatan')
            ->call('toggleStatus', $toOpen->id);

        $this->assertSame('aktif', $toOpen->fresh()->status);
        $this->assertSame('ditutup', $alreadyOpen->fresh()->status);
    }

    public function test_closing_the_active_event_does_not_affect_others(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $active = Event::factory()->create(['status' => 'aktif']);
        $other = Event::factory()->create(['status' => 'selesai']);

        Volt::actingAs($admin)
            ->test('pages.admin.kegiatan')
            ->call('toggleStatus', $active->id);

        $this->assertSame('ditutup', $active->fresh()->status);
        $this->assertSame('selesai', $other->fresh()->status);
    }
}
