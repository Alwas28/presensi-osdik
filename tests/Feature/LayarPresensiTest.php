<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * Covers the "QR di /admin/layar belum bisa terbaca" report: qrPayload used to
 * be a #[Computed] property only ever read from Alpine JS, so Livewire's
 * wire:poll wasn't reliably pushing a fresh value to the displayed QR image —
 * the same bug already fixed once for the mahasiswa self-QR and the Scan
 * Presensi "QR Kegiatan" tab. qrPayload is now a plain property, mirroring the
 * already-working Admin\QrModal ("Lihat QR" button).
 */
class LayarPresensiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_populates_the_qr_payload_for_the_active_event_on_mount(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $event = Event::factory()->create(['status' => 'aktif']);

        Volt::actingAs($admin)
            ->test('pages.admin.layar')
            ->assertSet('qrPayload', fn (?string $payload) => $payload === "EVT:{$event->id}:{$event->fresh()->current_token}");
    }

    public function test_it_selects_the_requested_event_via_the_url_parameter(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        Event::factory()->create(['status' => 'aktif']);
        $requested = Event::factory()->create(['status' => 'ditutup']);

        Volt::actingAs($admin)
            ->test('pages.admin.layar', ['eventId' => $requested->id])
            ->assertSet('eventId', $requested->id)
            ->assertSet('qrPayload', null);
    }

    public function test_tick_rotates_an_expired_token(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $event = Event::factory()->create(['status' => 'aktif']);
        $staleToken = $event->rotateToken();
        $event->forceFill(['token_generated_at' => now()->subMinutes(5)])->save();

        $component = Volt::actingAs($admin)
            ->test('pages.admin.layar')
            ->call('tick');

        $this->assertNotSame($staleToken, $event->fresh()->current_token);
        $component->assertSet('qrPayload', "EVT:{$event->id}:{$event->fresh()->current_token}");
    }
}
