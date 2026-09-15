<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * The "presensi" role's Scan Presensi page now mirrors the mahasiswa presensi
 * page's two-tab layout: scanning a student's QR, or displaying the active
 * event's own QR for a student to scan.
 */
class ScanPresensiTabsTest extends TestCase
{
    use RefreshDatabase;

    public function test_switching_to_the_qr_tab_populates_the_event_qr_payload(): void
    {
        $petugas = User::factory()->create(['role' => 'presensi']);
        $event = Event::factory()->create(['status' => 'aktif']);

        Volt::actingAs($petugas)
            ->test('pages.admin.scan-presensi')
            ->assertSet('tab', 'scan')
            ->call('setTab', 'qr')
            ->assertSet('tab', 'qr')
            ->assertSet('eventQrPayload', fn (?string $payload) => $payload === "EVT:{$event->id}:{$event->fresh()->current_token}");
    }

    public function test_event_qr_tab_shows_empty_state_without_an_active_event(): void
    {
        $petugas = User::factory()->create(['role' => 'presensi']);

        Volt::actingAs($petugas)
            ->test('pages.admin.scan-presensi')
            ->call('setTab', 'qr')
            ->assertSee('Belum ada kegiatan yang dibuka');
    }

    public function test_refreshing_event_qr_rotates_an_expired_token(): void
    {
        $petugas = User::factory()->create(['role' => 'presensi']);
        $event = Event::factory()->create(['status' => 'aktif']);
        $staleToken = $event->rotateToken();
        $event->forceFill(['token_generated_at' => now()->subMinutes(5)])->save();

        $component = Volt::actingAs($petugas)
            ->test('pages.admin.scan-presensi')
            ->call('setTab', 'qr');

        $this->assertNotSame($staleToken, $event->fresh()->current_token);
        $component->assertSet('eventQrPayload', "EVT:{$event->id}:{$event->fresh()->current_token}");
    }
}
