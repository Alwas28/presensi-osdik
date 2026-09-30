<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Fakultas;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\Sertifikat;
use App\Models\SertifikatSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class SertifikatTest extends TestCase
{
    use RefreshDatabase;

    private function makeMahasiswaUser(string $nama = 'Ahmad Fauzan'): User
    {
        $prodi = ProgramStudi::factory()->for(Fakultas::factory())->create();
        $mahasiswa = Mahasiswa::factory()->for($prodi, 'programStudi')->create(['nama' => $nama]);

        return User::factory()->create(['mahasiswa_id' => $mahasiswa->id, 'role' => 'mahasiswa']);
    }

    private function giveAttendance(User $user): void
    {
        Attendance::factory()->for(Mahasiswa::find($user->mahasiswa_id), 'mahasiswa')->create();
    }

    public function test_mahasiswa_who_attended_can_generate_a_certificate(): void
    {
        $user = $this->makeMahasiswaUser();
        $this->giveAttendance($user);

        Volt::actingAs($user)
            ->test('pages.mahasiswa.profil')
            ->assertSet('sertifikat', null)
            ->call('generateSertifikat');

        $this->assertDatabaseHas('sertifikats', ['mahasiswa_id' => $user->mahasiswa_id]);
    }

    public function test_mahasiswa_without_attendance_cannot_generate_a_certificate(): void
    {
        $user = $this->makeMahasiswaUser();

        Volt::actingAs($user)
            ->test('pages.mahasiswa.profil')
            ->call('generateSertifikat')
            ->assertSet('sertifikatError', 'Kamu tidak diizinkan generate sertifikat karena tidak mengikuti kegiatan Osdik.')
            ->assertSee('tidak diizinkan generate sertifikat');

        $this->assertDatabaseMissing('sertifikats', ['mahasiswa_id' => $user->mahasiswa_id]);
    }

    public function test_generating_twice_does_not_create_a_duplicate(): void
    {
        $user = $this->makeMahasiswaUser();
        $this->giveAttendance($user);

        Volt::actingAs($user)
            ->test('pages.mahasiswa.profil')
            ->call('generateSertifikat')
            ->call('generateSertifikat');

        $this->assertSame(1, Sertifikat::query()->where('mahasiswa_id', $user->mahasiswa_id)->count());
    }

    public function test_the_number_follows_the_configured_format(): void
    {
        $user = $this->makeMahasiswaUser();
        $this->giveAttendance($user);
        SertifikatSetting::current()->update(['format' => 'OSDIK-{urut}-{tahun}', 'digit_urut' => 4]);

        Volt::actingAs($user)->test('pages.mahasiswa.profil')->call('generateSertifikat');

        $nomor = Sertifikat::query()->where('mahasiswa_id', $user->mahasiswa_id)->value('nomor');
        $this->assertSame('OSDIK-0001-'.now()->format('Y'), $nomor);
    }

    public function test_pdf_view_renders_the_mahasiswa_name_and_certificate_number(): void
    {
        $html = view('pdf.sertifikat', [
            'nama' => 'Siti Contoh',
            'nomor' => '007/SERTIFIKAT/OSDIK/UMK/IX/2026',
            'backgroundBase64' => '',
        ])->render();

        $this->assertStringContainsString('Siti Contoh', $html);
        $this->assertStringContainsString('007/SERTIFIKAT/OSDIK/UMK/IX/2026', $html);
    }

    public function test_download_requires_a_generated_certificate(): void
    {
        $user = $this->makeMahasiswaUser();

        $this->actingAs($user)->get(route('mahasiswa.sertifikat.download'))->assertNotFound();
    }

    public function test_mahasiswa_can_download_their_own_generated_certificate(): void
    {
        $user = $this->makeMahasiswaUser('Budi Santoso');
        Sertifikat::factory()->create(['mahasiswa_id' => $user->mahasiswa_id, 'nomor' => '005/SERTIFIKAT/OSDIK/UMK/IX/2026']);

        $response = $this->actingAs($user)->get(route('mahasiswa.sertifikat.download'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('Sertifikat Osdik - Budi Santoso.pdf', $response->headers->get('content-disposition'));
    }

    public function test_each_mahasiswa_only_downloads_their_own_certificate(): void
    {
        $userA = $this->makeMahasiswaUser('Mahasiswa A');
        $userB = $this->makeMahasiswaUser('Mahasiswa B');
        Sertifikat::factory()->create(['mahasiswa_id' => $userA->mahasiswa_id, 'nomor' => '001/A']);

        $this->actingAs($userB)->get(route('mahasiswa.sertifikat.download'))->assertNotFound();
    }

    public function test_guests_cannot_download(): void
    {
        $this->get(route('mahasiswa.sertifikat.download'))->assertRedirect(route('login'));
    }
}
