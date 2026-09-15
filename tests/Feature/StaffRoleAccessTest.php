<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The "presensi" role can only scan/show QR codes at the venue — it must not
 * reach the fuller admin pages (dashboard, kegiatan, monitoring, data
 * mahasiswa, laporan), nor the super-admin-only pengguna/aduan pages.
 */
class StaffRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<array{0: string}>
     */
    public static function fullAdminRoutes(): array
    {
        return [
            ['admin.dashboard'],
            ['admin.kegiatan'],
            ['admin.monitoring'],
            ['admin.mahasiswa'],
            ['admin.laporan'],
        ];
    }

    /**
     * @return list<array{0: string}>
     */
    public static function superAdminOnlyRoutes(): array
    {
        return [
            ['admin.pengguna'],
            ['admin.aduan'],
        ];
    }

    #[DataProvider('fullAdminRoutes')]
    public function test_presensi_role_cannot_access_full_admin_pages(string $routeName): void
    {
        $petugas = User::factory()->create(['role' => 'presensi']);

        $this->actingAs($petugas)->get(route($routeName))->assertForbidden();
    }

    public function test_presensi_role_can_access_scan_presensi(): void
    {
        $petugas = User::factory()->create(['role' => 'presensi']);

        $this->actingAs($petugas)->get(route('admin.scan-presensi'))->assertOk();
    }

    public function test_presensi_role_can_access_layar(): void
    {
        $petugas = User::factory()->create(['role' => 'presensi']);

        $this->actingAs($petugas)->get(route('admin.layar'))->assertOk();
    }

    #[DataProvider('superAdminOnlyRoutes')]
    public function test_panitia_cannot_access_super_admin_only_pages(string $routeName): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);

        $this->actingAs($panitia)->get(route($routeName))->assertForbidden();
    }

    #[DataProvider('superAdminOnlyRoutes')]
    public function test_presensi_role_cannot_access_super_admin_only_pages(string $routeName): void
    {
        $petugas = User::factory()->create(['role' => 'presensi']);

        $this->actingAs($petugas)->get(route($routeName))->assertForbidden();
    }

    #[DataProvider('superAdminOnlyRoutes')]
    public function test_super_admin_can_access_super_admin_only_pages(string $routeName): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)->get(route($routeName))->assertOk();
    }

    public function test_dashboard_redirects_presensi_role_to_scan_presensi(): void
    {
        $petugas = User::factory()->create(['role' => 'presensi']);

        $this->actingAs($petugas)->get(route('dashboard'))->assertRedirect(route('admin.scan-presensi'));
    }
}
