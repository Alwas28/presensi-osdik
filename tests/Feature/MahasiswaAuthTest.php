<?php

namespace Tests\Feature;

use App\Models\Fakultas;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;
use Tests\TestCase;

class MahasiswaAuthTest extends TestCase
{
    use RefreshDatabase;

    private function makeMahasiswaUser(): Mahasiswa
    {
        $prodi = ProgramStudi::factory()->for(Fakultas::factory())->create();
        $mahasiswa = Mahasiswa::factory()->for($prodi, 'programStudi')->create(['nim' => '20260001']);

        User::factory()->create([
            'mahasiswa_id' => $mahasiswa->id,
            'email' => '20260001@mahasiswa.pkkmb.local',
            'password' => Hash::make('20260001'),
            'role' => 'mahasiswa',
        ]);

        return $mahasiswa;
    }

    public function test_mahasiswa_can_login_with_nim_and_default_password(): void
    {
        $this->makeMahasiswaUser();

        Volt::test('pages.mahasiswa.login')
            ->set('nim', '20260001')
            ->set('password', '20260001')
            ->call('login')
            ->assertRedirect(route('mahasiswa.beranda'));

        $this->assertAuthenticated();
    }

    public function test_mahasiswa_login_fails_with_wrong_password(): void
    {
        $this->makeMahasiswaUser();

        Volt::test('pages.mahasiswa.login')
            ->set('nim', '20260001')
            ->set('password', 'salah')
            ->call('login')
            ->assertHasErrors('nim');

        $this->assertGuest();
    }

    public function test_non_mahasiswa_role_is_forbidden_from_mahasiswa_routes(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)->get(route('mahasiswa.beranda'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_mahasiswa_login_not_the_admin_one(): void
    {
        $this->get(route('mahasiswa.beranda'))->assertRedirect(route('mahasiswa.login'));
        $this->get('/mahasiswa')->assertRedirect(route('mahasiswa.login'));
    }

    public function test_guest_hitting_admin_routes_still_goes_to_the_admin_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_mahasiswa_can_access_own_pages(): void
    {
        $mahasiswa = $this->makeMahasiswaUser();

        $this->actingAs($mahasiswa->user)->get(route('mahasiswa.beranda'))->assertOk();
        $this->actingAs($mahasiswa->user)->get(route('mahasiswa.kegiatan'))->assertOk();
        $this->actingAs($mahasiswa->user)->get(route('mahasiswa.riwayat'))->assertOk();
        $this->actingAs($mahasiswa->user)->get(route('mahasiswa.profil'))->assertOk();
    }
}
