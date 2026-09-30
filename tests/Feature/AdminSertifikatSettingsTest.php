<?php

namespace Tests\Feature;

use App\Models\Sertifikat;
use App\Models\SertifikatSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AdminSertifikatSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_update_the_certificate_number_format(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        Volt::actingAs($admin)
            ->test('pages.admin.sertifikat')
            ->set('format', 'OSDIK/{urut}/{tahun}')
            ->set('digitUrut', 4)
            ->set('nomorUrutTerakhir', 10)
            ->call('save')
            ->assertHasNoErrors();

        $setting = SertifikatSetting::current();
        $this->assertSame('OSDIK/{urut}/{tahun}', $setting->format);
        $this->assertSame(4, $setting->digit_urut);
        $this->assertSame(10, $setting->nomor_urut_terakhir);
    }

    public function test_preview_reflects_the_next_number(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        Volt::actingAs($admin)
            ->test('pages.admin.sertifikat')
            ->set('format', 'OSDIK/{urut}')
            ->set('digitUrut', 3)
            ->set('nomorUrutTerakhir', 5)
            ->assertSet('preview', 'OSDIK/006');
    }

    public function test_format_is_required(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        Volt::actingAs($admin)
            ->test('pages.admin.sertifikat')
            ->set('format', '')
            ->call('save')
            ->assertHasErrors(['format']);
    }

    public function test_the_page_lists_issued_certificates(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        Sertifikat::factory()->create();

        Volt::actingAs($admin)->test('pages.admin.sertifikat')->assertOk();
    }

    public function test_panitia_and_presensi_cannot_access_the_settings_page(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $presensi = User::factory()->create(['role' => 'presensi']);

        $this->actingAs($panitia)->get(route('admin.sertifikat'))->assertForbidden();
        $this->actingAs($presensi)->get(route('admin.sertifikat'))->assertForbidden();
    }
}
