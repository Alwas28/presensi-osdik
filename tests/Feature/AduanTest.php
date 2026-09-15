<?php

namespace Tests\Feature;

use App\Models\Aduan;
use App\Models\Fakultas;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AduanTest extends TestCase
{
    use RefreshDatabase;

    private function makeMahasiswaUser(): User
    {
        $prodi = ProgramStudi::factory()->for(Fakultas::factory())->create();
        $mahasiswa = Mahasiswa::factory()->for($prodi, 'programStudi')->create();

        return User::factory()->create(['mahasiswa_id' => $mahasiswa->id, 'role' => 'mahasiswa']);
    }

    public function test_mahasiswa_can_submit_an_aduan(): void
    {
        $user = $this->makeMahasiswaUser();

        Volt::actingAs($user)
            ->test('pages.mahasiswa.aduan')
            ->set('judul', 'Kendala presensi')
            ->set('pesan', 'QR tidak bisa dipindai sama sekali.')
            ->call('kirim')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('aduans', [
            'mahasiswa_id' => $user->mahasiswa_id,
            'judul' => 'Kendala presensi',
            'pesan' => 'QR tidak bisa dipindai sama sekali.',
        ]);
    }

    public function test_aduan_requires_judul_and_pesan(): void
    {
        $user = $this->makeMahasiswaUser();

        Volt::actingAs($user)
            ->test('pages.mahasiswa.aduan')
            ->set('judul', '')
            ->set('pesan', '')
            ->call('kirim')
            ->assertHasErrors(['judul', 'pesan']);
    }

    public function test_mahasiswa_only_sees_their_own_aduan_history(): void
    {
        $user = $this->makeMahasiswaUser();
        $other = $this->makeMahasiswaUser();

        Aduan::factory()->create(['mahasiswa_id' => $user->mahasiswa_id, 'judul' => 'Punyaku']);
        Aduan::factory()->create(['mahasiswa_id' => $other->mahasiswa_id, 'judul' => 'Punya orang lain']);

        Volt::actingAs($user)
            ->test('pages.mahasiswa.aduan')
            ->assertSee('Punyaku')
            ->assertDontSee('Punya orang lain');
    }

    public function test_super_admin_sees_all_aduan_on_the_admin_page(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        Aduan::factory()->create(['judul' => 'Aduan mahasiswa A']);

        Volt::actingAs($admin)
            ->test('pages.admin.aduan')
            ->assertSee('Aduan mahasiswa A');
    }

    public function test_super_admin_can_mark_an_aduan_as_read(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $aduan = Aduan::factory()->create();

        Volt::actingAs($admin)
            ->test('pages.admin.aduan')
            ->call('tandaiDibaca', $aduan->id);

        $this->assertNotNull($aduan->fresh()->read_at);
    }
}
