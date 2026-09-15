<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Fakultas;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * Covers the "panitia tidak bisa scan QR mahasiswa" report: selfQrPayload used to
 * be a #[Computed] property only ever read from Alpine JS, which Livewire never
 * includes in its normal component payload — so wire:poll wasn't reliably
 * refreshing the token the student's QR actually displayed. It's now a plain
 * property set by an explicit action, mirroring Admin\QrModal's $qrPayload.
 */
class PresensiSelfQrRefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_self_qr_payload_is_populated_when_switching_to_show_method(): void
    {
        $prodi = ProgramStudi::factory()->for(Fakultas::factory())->create();
        $mahasiswa = Mahasiswa::factory()->for($prodi, 'programStudi')->create();
        $user = User::factory()->create(['mahasiswa_id' => $mahasiswa->id, 'role' => 'mahasiswa']);
        Event::factory()->create(['status' => 'aktif']);

        Volt::actingAs($user)
            ->test('pages.mahasiswa.presensi')
            ->assertSet('selfQrPayload', null)
            ->call('setMethod', 'show')
            ->assertSet('method', 'show')
            ->assertSet('selfQrPayload', fn (?string $payload) => $payload !== null && str_starts_with($payload, 'SELF:'));
    }

    public function test_refresh_self_qr_rotates_to_a_new_token(): void
    {
        $prodi = ProgramStudi::factory()->for(Fakultas::factory())->create();
        $mahasiswa = Mahasiswa::factory()->for($prodi, 'programStudi')->create();
        $user = User::factory()->create(['mahasiswa_id' => $mahasiswa->id, 'role' => 'mahasiswa']);
        Event::factory()->create(['status' => 'aktif']);

        $component = Volt::actingAs($user)
            ->test('pages.mahasiswa.presensi')
            ->call('setMethod', 'show');

        $firstPayload = $component->get('selfQrPayload');

        $component->call('refreshSelfQr');

        $this->assertNotSame($firstPayload, $component->get('selfQrPayload'));
    }
}
