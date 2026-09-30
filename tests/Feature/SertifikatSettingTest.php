<?php

namespace Tests\Feature;

use App\Models\SertifikatSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SertifikatSettingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_current_creates_a_singleton_with_sane_defaults(): void
    {
        $setting = SertifikatSetting::current();

        $this->assertSame('{urut}/SERTIFIKAT/OSDIK/UMK/{bulan_romawi}/{tahun}', $setting->format);
        $this->assertSame(3, $setting->digit_urut);
        $this->assertSame(0, $setting->nomor_urut_terakhir);
        $this->assertSame(1, SertifikatSetting::query()->count());
    }

    public function test_current_always_returns_the_same_row(): void
    {
        $first = SertifikatSetting::current();
        $second = SertifikatSetting::current();

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, SertifikatSetting::query()->count());
    }

    public function test_format_nomor_pads_the_sequence_and_fills_month_and_year(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-15'));

        $setting = SertifikatSetting::current();
        $setting->format = '{urut}/SERTIFIKAT/OSDIK/UMK/{bulan_romawi}/{tahun}';
        $setting->digit_urut = 4;

        $this->assertSame('0007/SERTIFIKAT/OSDIK/UMK/IX/2026', $setting->formatNomor(7));
    }

    public function test_generate_nomor_increments_sequentially_and_persists(): void
    {
        $setting = SertifikatSetting::current();

        $first = $setting->generateNomor();
        $second = $setting->generateNomor();

        $this->assertStringStartsWith('001/', $first);
        $this->assertStringStartsWith('002/', $second);
        $this->assertSame(2, SertifikatSetting::current()->nomor_urut_terakhir);
    }
}
