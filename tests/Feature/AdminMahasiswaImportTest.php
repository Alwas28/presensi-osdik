<?php

namespace Tests\Feature;

use App\Imports\MahasiswaImport;
use App\Models\Fakultas;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminMahasiswaImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_mahasiswa_and_resolves_known_fakultas_names(): void
    {
        $import = new MahasiswaImport;

        $import->collection(new Collection([
            [
                'no_registrasi' => '261260700',
                'nim' => '22681016',
                'nama' => 'Nur Aini',
                'no_telp' => '6208137503624',
                'status_pendaftar' => 'Mahasiswa Baru',
                'jalur' => 'Umum-D3, D4, S1',
                'prodi' => 'Pendidikan Agama Islam',
                'fakultas' => 'AGAMA ISLAM',
            ],
        ]));

        $this->assertSame(1, $import->created);
        $this->assertSame(0, $import->updated);
        $this->assertSame(0, $import->skipped);

        $this->assertDatabaseHas('fakultas', ['code' => 'FAI', 'name' => 'Fakultas Agama Islam']);

        $mahasiswa = Mahasiswa::query()->where('nim', '22681016')->first();
        $this->assertNotNull($mahasiswa);
        $this->assertSame('Nur Aini', $mahasiswa->nama);
        $this->assertSame('Pendidikan Agama Islam', $mahasiswa->programStudi->name);
        $this->assertSame('Fakultas Agama Islam', $mahasiswa->programStudi->fakultas->name);
    }

    public function test_it_provisions_a_login_account_with_nim_as_default_password(): void
    {
        $import = new MahasiswaImport;
        $import->collection(new Collection([
            [
                'nim' => '22681016',
                'nama' => 'Nur Aini',
                'prodi' => 'Pendidikan Agama Islam',
                'fakultas' => 'AGAMA ISLAM',
            ],
        ]));

        $mahasiswa = Mahasiswa::query()->where('nim', '22681016')->first();
        $user = User::query()->where('mahasiswa_id', $mahasiswa->id)->first();

        $this->assertNotNull($user);
        $this->assertSame('mahasiswa', $user->role);
        $this->assertSame('22681016@mahasiswa.osdik.local', $user->email);
        $this->assertTrue(Hash::check('22681016', $user->password));
    }

    public function test_it_does_not_reset_an_existing_login_password_on_reimport(): void
    {
        $import = new MahasiswaImport;
        $row = [
            'nim' => '22681016',
            'nama' => 'Nur Aini',
            'prodi' => 'Pendidikan Agama Islam',
            'fakultas' => 'AGAMA ISLAM',
        ];
        $import->collection(new Collection([$row]));

        $mahasiswa = Mahasiswa::query()->where('nim', '22681016')->first();
        $user = User::query()->where('mahasiswa_id', $mahasiswa->id)->first();
        $user->update(['password' => Hash::make('password-baru')]);

        (new MahasiswaImport)->collection(new Collection([$row]));

        $this->assertTrue(Hash::check('password-baru', $user->fresh()->password));
    }

    public function test_it_updates_an_existing_mahasiswa_by_nim(): void
    {
        $fakultas = Fakultas::factory()->create();
        $prodi = $fakultas->programStudis()->create(['name' => 'Lama']);
        Mahasiswa::factory()->for($prodi, 'programStudi')->create(['nim' => '999', 'nama' => 'Nama Lama']);

        $import = new MahasiswaImport;
        $import->collection(new Collection([
            [
                'nim' => '999',
                'nama' => 'Nama Baru',
                'prodi' => 'Ilmu Hukum',
                'fakultas' => 'HUKUM',
            ],
        ]));

        $this->assertSame(0, $import->created);
        $this->assertSame(1, $import->updated);
        $this->assertSame('Nama Baru', Mahasiswa::query()->where('nim', '999')->first()->nama);
    }

    public function test_it_skips_rows_missing_required_fields(): void
    {
        $import = new MahasiswaImport;
        $import->collection(new Collection([
            ['nim' => '', 'nama' => 'Tanpa NIM', 'prodi' => 'Ilmu Hukum', 'fakultas' => 'HUKUM'],
        ]));

        $this->assertSame(1, $import->skipped);
        $this->assertNotEmpty($import->errors);
    }
}
