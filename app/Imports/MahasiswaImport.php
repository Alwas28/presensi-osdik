<?php

namespace App\Imports;

use App\Models\Fakultas;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Imports the registrar's Excel export of new-student data.
 *
 * Expected columns: No Registrasi, NIM, Nama, No Telp, Status Pendaftar, Jalur, Prodi, Fakultas.
 */
class MahasiswaImport implements ToCollection, WithHeadingRow
{
    /**
     * Known faculty labels (as they appear in the registrar's export) mapped to a
     * short code and a display name, matching the codes used across the admin UI.
     *
     * @var array<string, array{code: string, name: string}>
     */
    private const FAKULTAS_MAP = [
        'AGAMA ISLAM' => ['code' => 'FAI', 'name' => 'Fakultas Agama Islam'],
        'HUKUM' => ['code' => 'FH', 'name' => 'Fakultas Hukum'],
        'KEGURUAN DAN ILMU PENDIDIKAN' => ['code' => 'FKIP', 'name' => 'Fakultas Keguruan dan Ilmu Pendidikan'],
        'TEKNIK' => ['code' => 'FT', 'name' => 'Fakultas Teknik'],
        'EKONOMI BISNIS ISLAM' => ['code' => 'FEBI', 'name' => 'Fakultas Ekonomi dan Bisnis Islam'],
        'KEDOKTERAN DAN ILMU KESEHATAN' => ['code' => 'FKIK', 'name' => 'Fakultas Kedokteran dan Ilmu Kesehatan'],
        'PERTANIAN' => ['code' => 'FP', 'name' => 'Fakultas Pertanian'],
        'PERIKANAN & ILMU KELAUTAN' => ['code' => 'FPIK', 'name' => 'Fakultas Perikanan dan Ilmu Kelautan'],
        'ILMU SOSIAL DAN ILMU POLITIK' => ['code' => 'FISIP', 'name' => 'Fakultas Ilmu Sosial dan Ilmu Politik'],
    ];

    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    /** @var list<string> */
    public array $errors = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $nim = trim((string) ($row['nim'] ?? ''));
            $nama = trim((string) ($row['nama'] ?? ''));
            $fakultasRaw = trim((string) ($row['fakultas'] ?? ''));
            $prodiName = trim((string) ($row['prodi'] ?? ''));

            if ($nim === '' || $nama === '' || $prodiName === '' || $fakultasRaw === '') {
                $this->skipped++;
                $this->errors[] = 'Baris '.($index + 2).': NIM, Nama, Prodi, dan Fakultas wajib diisi.';

                continue;
            }

            $known = self::FAKULTAS_MAP[Str::upper($fakultasRaw)] ?? null;
            $fakultasName = $known['name'] ?? 'Fakultas '.Str::title(Str::lower($fakultasRaw));
            $fakultasCode = $known['code'] ?? Str::upper(Str::substr(preg_replace('/[^A-Za-z]/', '', $fakultasRaw) ?: 'FAK', 0, 4));

            $fakultas = Fakultas::query()->firstOrCreate(
                ['name' => $fakultasName],
                ['code' => $fakultasCode]
            );

            $prodi = ProgramStudi::query()->firstOrCreate([
                'fakultas_id' => $fakultas->id,
                'name' => $prodiName,
            ]);

            $exists = Mahasiswa::query()->where('nim', $nim)->exists();

            $mahasiswa = Mahasiswa::query()->updateOrCreate(
                ['nim' => $nim],
                [
                    'no_registrasi' => trim((string) ($row['no_registrasi'] ?? '')) ?: null,
                    'nama' => $nama,
                    'no_telp' => trim((string) ($row['no_telp'] ?? '')) ?: null,
                    'status_pendaftar' => trim((string) ($row['status_pendaftar'] ?? '')) ?: null,
                    'jalur' => trim((string) ($row['jalur'] ?? '')) ?: null,
                    'program_studi_id' => $prodi->id,
                ]
            );

            $this->provisionLoginFor($mahasiswa);

            $exists ? $this->updated++ : $this->created++;
        }
    }

    /**
     * Every imported student gets a login account: NIM as the identifier and
     * NIM as the default password. Existing accounts (and any password the
     * student already changed) are left untouched on re-import.
     */
    private function provisionLoginFor(Mahasiswa $mahasiswa): void
    {
        // Check first so Hash::make() (deliberately slow) only runs for genuinely new accounts.
        if (User::query()->where('mahasiswa_id', $mahasiswa->id)->exists()) {
            return;
        }

        User::query()->create([
            'mahasiswa_id' => $mahasiswa->id,
            'name' => $mahasiswa->nama,
            'email' => "{$mahasiswa->nim}@mahasiswa.pkkmb.local",
            'password' => Hash::make($mahasiswa->nim),
            'role' => 'mahasiswa',
            'email_verified_at' => now(),
        ]);
    }
}
