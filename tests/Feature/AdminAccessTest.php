<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<string>
     */
    public static function adminRoutes(): array
    {
        return [
            ['admin.dashboard'],
            ['admin.kegiatan'],
            ['admin.monitoring'],
            ['admin.mahasiswa'],
            ['admin.laporan'],
        ];
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_mahasiswa_role_cannot_access_admin(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);

        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    }

    #[DataProvider('adminRoutes')]
    public function test_super_admin_can_access_every_admin_page(string $routeName): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)->get(route($routeName))->assertOk();
    }

    public function test_panitia_can_access_admin_dashboard(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);

        $this->actingAs($panitia)->get(route('admin.dashboard'))->assertOk();
    }
}
