<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Singleton row (id=1) holding the certificate numbering format and the
 * running sequence counter used to generate each mahasiswa's nomor sertifikat.
 */
#[Fillable(['format', 'digit_urut', 'nomor_urut_terakhir'])]
class SertifikatSetting extends Model
{
    protected function casts(): array
    {
        return [
            'digit_urut' => 'integer',
            'nomor_urut_terakhir' => 'integer',
        ];
    }

    public static function current(): self
    {
        // Whichever row exists is "the" singleton — searching by a hardcoded
        // id=1 doesn't work here since 'id' isn't mass-assignable, so
        // firstOrCreate(['id' => 1], ...) would silently drop it and create a
        // new auto-incremented row every time none matched.
        return static::query()->first() ?? static::query()->create([
            'format' => '{urut}/SERTIFIKAT/OSDIK/UMK/{bulan_romawi}/{tahun}',
            'digit_urut' => 3,
            'nomor_urut_terakhir' => 0,
        ]);
    }

    /**
     * Atomically claims the next sequence number and returns the formatted
     * nomor sertifikat, so two "Generate Sertifikat" clicks arriving at the
     * same time can never end up with the same number.
     */
    public function generateNomor(): string
    {
        return DB::transaction(function () {
            $setting = static::query()->lockForUpdate()->findOrFail($this->id);
            $setting->nomor_urut_terakhir++;
            $setting->save();

            return $setting->formatNomor($setting->nomor_urut_terakhir);
        });
    }

    /**
     * Renders a sequence number through this setting's format — used both by
     * generateNomor() and by the settings page to preview the next number
     * without actually claiming it.
     */
    public function formatNomor(int $urut): string
    {
        return strtr($this->format, [
            '{urut}' => str_pad((string) $urut, $this->digit_urut, '0', STR_PAD_LEFT),
            '{bulan_romawi}' => self::monthToRoman((int) now()->format('n')),
            '{tahun}' => now()->format('Y'),
        ]);
    }

    private static function monthToRoman(int $month): string
    {
        $numerals = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        return $numerals[$month] ?? (string) $month;
    }
}
