<?php

namespace App\Console\Commands;

use App\Models\Mahasiswa;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * One-off import of the campus-issued email accounts (NIM, email address,
 * password) into mahasiswas.email / mahasiswas.email_password, so the
 * mahasiswa side can show them back to the student without reading the
 * spreadsheet on every request.
 *
 * Expected columns: Fakultas, Prodi, Nim, Nama, Email address, Password.
 */
#[Signature('app:import-email-kampus {path? : Path to the xlsx file (defaults to storage/app/private/email.xlsx)}')]
#[Description('Import campus email accounts (NIM, email, password) from an xlsx file into mahasiswas')]
class ImportEmailKampus extends Command
{
    public function handle(): int
    {
        $path = $this->argument('path') ?? storage_path('app/private/email.xlsx');

        if (! is_string($path) || ! file_exists($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $rows = IOFactory::load($path)->getActiveSheet()->toArray();
        array_shift($rows); // header row: Fakultas, Prodi, Nim, Nama, Email address, Password

        $updated = 0;
        $notFound = 0;

        foreach ($rows as $row) {
            $nim = trim((string) ($row[2] ?? ''));
            $email = trim((string) ($row[4] ?? ''));
            $password = trim((string) ($row[5] ?? ''));

            if ($nim === '') {
                continue;
            }

            $affected = Mahasiswa::query()->where('nim', $nim)->update([
                'email' => $email ?: null,
                'email_password' => $password ?: null,
            ]);

            $affected ? $updated++ : $notFound++;
        }

        $this->info("Selesai: {$updated} mahasiswa diperbarui, {$notFound} NIM tidak ditemukan.");

        return self::SUCCESS;
    }
}
