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

class AdminMahasiswaAddTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_a_new_mahasiswa(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $fakultas = Fakultas::factory()->create();
        $prodi = ProgramStudi::factory()->for($fakultas)->create();

        Volt::actingAs($admin)
            ->test('pages.admin.mahasiswa')
            ->set('newNim', '22999001')
            ->set('newNama', 'Budi Santoso')
            ->set('newFakultasId', (string) $fakultas->id)
            ->set('newProdiId', (string) $prodi->id)
            ->call('saveMahasiswa')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('mahasiswas', [
            'nim' => '22999001',
            'nama' => 'Budi Santoso',
            'program_studi_id' => $prodi->id,
        ]);
    }

    public function test_a_login_account_is_provisioned_for_the_new_mahasiswa(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $fakultas = Fakultas::factory()->create();
        $prodi = ProgramStudi::factory()->for($fakultas)->create();

        Volt::actingAs($admin)
            ->test('pages.admin.mahasiswa')
            ->set('newNim', '22999002')
            ->set('newNama', 'Ani Wijaya')
            ->set('newFakultasId', (string) $fakultas->id)
            ->set('newProdiId', (string) $prodi->id)
            ->call('saveMahasiswa');

        $mahasiswa = Mahasiswa::query()->where('nim', '22999002')->firstOrFail();
        $user = User::query()->where('mahasiswa_id', $mahasiswa->id)->first();

        $this->assertNotNull($user);
        $this->assertSame('22999002@mahasiswa.osdik.local', $user->email);
        $this->assertSame('mahasiswa', $user->role);
        $this->assertTrue(Hash::check('22999002', $user->password));
    }

    public function test_nim_must_be_unique(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $fakultas = Fakultas::factory()->create();
        $prodi = ProgramStudi::factory()->for($fakultas)->create();
        Mahasiswa::factory()->for($prodi, 'programStudi')->create(['nim' => '22999003']);

        Volt::actingAs($admin)
            ->test('pages.admin.mahasiswa')
            ->set('newNim', '22999003')
            ->set('newNama', 'Duplikat')
            ->set('newFakultasId', (string) $fakultas->id)
            ->set('newProdiId', (string) $prodi->id)
            ->call('saveMahasiswa')
            ->assertHasErrors(['newNim']);
    }

    public function test_nim_nama_fakultas_and_prodi_are_required(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        Volt::actingAs($admin)
            ->test('pages.admin.mahasiswa')
            ->set('newNim', '')
            ->set('newNama', '')
            ->set('newFakultasId', '')
            ->set('newProdiId', '')
            ->call('saveMahasiswa')
            ->assertHasErrors(['newNim', 'newNama', 'newFakultasId', 'newProdiId']);
    }

    public function test_prodi_options_are_scoped_to_the_selected_fakultas(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $fakultasA = Fakultas::factory()->create();
        $fakultasB = Fakultas::factory()->create();
        ProgramStudi::factory()->for($fakultasA)->create(['name' => 'Prodi A']);
        ProgramStudi::factory()->for($fakultasB)->create(['name' => 'Prodi B']);

        Volt::actingAs($admin)
            ->test('pages.admin.mahasiswa')
            ->call('openAddModal')
            ->set('newFakultasId', (string) $fakultasA->id)
            ->assertSee('Prodi A')
            ->assertDontSee('Prodi B');
    }
}
