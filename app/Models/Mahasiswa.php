<?php

namespace App\Models;

use Database\Factories\MahasiswaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Hash;

#[Fillable([
    'no_registrasi',
    'nim',
    'nama',
    'no_telp',
    'status_pendaftar',
    'jalur',
    'program_studi_id',
    'angkatan',
    'email',
])]
class Mahasiswa extends Model
{
    /** @use HasFactory<MahasiswaFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<ProgramStudi, $this>
     */
    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class);
    }

    /**
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * @return HasMany<Aduan, $this>
     */
    public function aduans(): HasMany
    {
        return $this->hasMany(Aduan::class);
    }

    public function fakultas(): ?Fakultas
    {
        return $this->programStudi?->fakultas;
    }

    /**
     * @return HasOne<User, $this>
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * Every mahasiswa gets a login account: NIM as the identifier and NIM as
     * the default password. Existing accounts (and any password the student
     * already changed) are left untouched — safe to call repeatedly.
     */
    public function ensureLoginAccount(): User
    {
        // Check first so Hash::make() (deliberately slow) only runs for genuinely
        // new accounts — matters when this runs once per row during a bulk import.
        $existing = User::query()->where('mahasiswa_id', $this->id)->first();

        if ($existing) {
            return $existing;
        }

        return User::query()->create([
            'mahasiswa_id' => $this->id,
            'name' => $this->nama,
            'email' => "{$this->nim}@mahasiswa.osdik.local",
            'password' => Hash::make($this->nim),
            'role' => 'mahasiswa',
            'email_verified_at' => now(),
        ]);
    }
}
