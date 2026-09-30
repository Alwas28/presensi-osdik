<?php

namespace Tests\Feature;

use App\Models\Fakultas;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Volt\Volt;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class EmailKampusTest extends TestCase
{
    use RefreshDatabase;

    private function makeMahasiswaUser(?string $email = null, ?string $emailPassword = null): User
    {
        $prodi = ProgramStudi::factory()->for(Fakultas::factory())->create();
        $mahasiswa = Mahasiswa::factory()->for($prodi, 'programStudi')->create([
            'email' => $email,
            'email_password' => $emailPassword,
        ]);

        return User::factory()->create(['mahasiswa_id' => $mahasiswa->id, 'role' => 'mahasiswa']);
    }

    public function test_mahasiswa_with_an_account_can_see_it_on_the_profile_page(): void
    {
        $user = $this->makeMahasiswaUser('budi.22681001@student.umkendari.ac.id', 'R4has1a!9xK');

        Volt::actingAs($user)
            ->test('pages.mahasiswa.profil')
            ->assertSee('Lihat Akun Email Kampus')
            ->assertSee('budi.22681001@student.umkendari.ac.id')
            ->assertSee('R4has1a!9xK');
    }

    public function test_mahasiswa_without_an_account_sees_a_fallback_message(): void
    {
        $user = $this->makeMahasiswaUser();

        Volt::actingAs($user)
            ->test('pages.mahasiswa.profil')
            ->assertDontSee('Lihat Akun Email Kampus')
            ->assertSee('belum tersedia');
    }

    public function test_email_password_is_hidden_from_array_and_json_serialization(): void
    {
        $prodi = ProgramStudi::factory()->for(Fakultas::factory())->create();
        $mahasiswa = Mahasiswa::factory()->for($prodi, 'programStudi')->create([
            'email' => 'siti@student.umkendari.ac.id',
            'email_password' => 'RahasiaBanget123',
        ]);

        $this->assertArrayNotHasKey('email_password', $mahasiswa->toArray());
        $this->assertStringNotContainsString('RahasiaBanget123', $mahasiswa->toJson());
    }

    public function test_import_command_fills_in_email_and_password_by_nim(): void
    {
        $prodi = ProgramStudi::factory()->for(Fakultas::factory())->create();
        $matched = Mahasiswa::factory()->for($prodi, 'programStudi')->create(['nim' => '22681099']);
        $unmatched = Mahasiswa::factory()->for($prodi, 'programStudi')->create(['nim' => '22681100', 'email' => 'original@example.com']);

        $path = storage_path('framework/testing/email-import-'.uniqid().'.xlsx');
        File::ensureDirectoryExists(dirname($path));

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['Fakultas', 'Prodi', 'Nim', 'Nama', 'Email address', 'Password'],
            ['Fakultas Teknik', 'Informatika', '22681099', 'Contoh Nama', 'contoh.22681099@student.umkendari.ac.id', 'S3cretPass!1'],
            ['Fakultas Teknik', 'Informatika', '99999999', 'Tidak Ada', 'tidakada@student.umkendari.ac.id', 'xxx'],
        ]);
        (new Xlsx($spreadsheet))->save($path);

        $this->artisan('app:import-email-kampus', ['path' => $path])
            ->expectsOutputToContain('1 mahasiswa diperbarui, 1 NIM tidak ditemukan')
            ->assertSuccessful();

        $matched->refresh();
        $this->assertSame('contoh.22681099@student.umkendari.ac.id', $matched->email);
        $this->assertSame('S3cretPass!1', $matched->email_password);

        $unmatched->refresh();
        $this->assertSame('original@example.com', $unmatched->email);

        File::delete($path);
    }

    public function test_import_command_fails_gracefully_when_the_file_is_missing(): void
    {
        $this->artisan('app:import-email-kampus', ['path' => storage_path('framework/testing/does-not-exist.xlsx')])
            ->assertFailed();
    }
}
