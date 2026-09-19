<?php

namespace Tests\Feature;

use App\Exports\PresensiExport;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\Fakultas;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class PresensiExportTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    private Mahasiswa $hadir;

    private Mahasiswa $belum;

    protected function setUp(): void
    {
        parent::setUp();

        $prodi = ProgramStudi::factory()->for(Fakultas::factory())->create();
        $this->event = Event::factory()->create(['nama' => 'Pengenalan Fakultas']);
        $this->hadir = Mahasiswa::factory()->for($prodi, 'programStudi')->create(['nim' => '22000001', 'nama' => 'Ani Hadir']);
        $this->belum = Mahasiswa::factory()->for($prodi, 'programStudi')->create(['nim' => '22000002', 'nama' => 'Budi Belum']);

        Attendance::factory()->for($this->hadir, 'mahasiswa')->for($this->event)->create(['channel' => 'manual']);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function exportedRows(PresensiExport $export): array
    {
        $path = storage_path('framework/testing/presensi-export-'.uniqid().'.xlsx');
        File::ensureDirectoryExists(dirname($path));
        file_put_contents($path, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));

        $rows = IOFactory::load($path)->getActiveSheet()->toArray();
        File::delete($path);

        return $rows;
    }

    public function test_export_lists_every_mahasiswa_with_their_status_by_default(): void
    {
        $rows = $this->exportedRows(new PresensiExport($this->event));

        $this->assertSame(['NIM', 'Nama', 'Fakultas', 'Program Studi', 'Jalur', 'Kegiatan', 'Status', 'Waktu Presensi', 'Metode'], $rows[0]);
        $this->assertCount(3, $rows);
        $this->assertSame('Ani Hadir', $rows[1][1]);
        $this->assertSame('Hadir', $rows[1][6]);
        $this->assertSame('Presensi manual', $rows[1][8]);
        $this->assertSame('Belum hadir', $rows[2][6]);
        $this->assertNull($rows[2][7]);
    }

    public function test_status_filter_limits_the_rows(): void
    {
        $hadirRows = $this->exportedRows(new PresensiExport($this->event, status: 'hadir'));
        $belumRows = $this->exportedRows(new PresensiExport($this->event, status: 'belum'));

        $this->assertCount(2, $hadirRows);
        $this->assertSame('Ani Hadir', $hadirRows[1][1]);
        $this->assertCount(2, $belumRows);
        $this->assertSame('Budi Belum', $belumRows[1][1]);
    }

    public function test_search_filter_matches_name_or_nim(): void
    {
        $rows = $this->exportedRows(new PresensiExport($this->event, search: '22000002'));

        $this->assertCount(2, $rows);
        $this->assertSame('Budi Belum', $rows[1][1]);
    }

    public function test_attendance_from_other_events_is_not_counted(): void
    {
        $other = Event::factory()->create();
        Attendance::factory()->for($this->belum, 'mahasiswa')->for($other)->create();

        $rows = $this->exportedRows(new PresensiExport($this->event, status: 'belum'));

        $this->assertSame('Budi Belum', $rows[1][1]);
    }

    public function test_admin_can_download_the_export(): void
    {
        $admin = User::factory()->create(['role' => 'panitia']);

        $response = $this->actingAs($admin)->get(route('admin.monitoring.export', ['event' => $this->event->id, 'status' => 'hadir']));

        $response->assertOk();
        $this->assertStringContainsString('Presensi - Pengenalan Fakultas.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_presensi_role_and_guests_cannot_download_the_export(): void
    {
        $url = route('admin.monitoring.export', ['event' => $this->event->id]);

        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'presensi']))->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'mahasiswa']))->get($url)->assertForbidden();
    }

    public function test_an_event_is_required(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)->get(route('admin.monitoring.export'))->assertSessionHasErrors('event');
    }
}
