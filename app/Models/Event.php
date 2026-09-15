<?php

namespace App\Models;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'nama',
    'tanggal',
    'jam_mulai',
    'jam_selesai',
    'lokasi',
    'deskripsi',
    'status',
    'metode_presensi',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'token_generated_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Rotate the dynamic QR token, persisting it so it can be validated later.
     */
    public function rotateToken(): string
    {
        $this->current_token = Str::random(40);
        $this->token_generated_at = now();
        $this->save();

        return $this->current_token;
    }

    /**
     * Whether the given token matches the current one and is still within its
     * rotation window (60 seconds), mirroring the countdown shown in the QR modal.
     */
    public function hasValidToken(string $token): bool
    {
        if (! $this->current_token || ! $this->token_generated_at || ! hash_equals($this->current_token, $token)) {
            return false;
        }

        return $this->token_generated_at->diffInSeconds(now(), true) <= 60;
    }

    /**
     * The "current" active event for a student: an aktif event they haven't
     * attended yet, if there is one. Falls back to any aktif event (which will
     * then read as already attended) rather than hiding presensi entirely just
     * because more than one session happens to be open at once.
     */
    public static function currentFor(Mahasiswa $mahasiswa): ?self
    {
        $attendedEventIds = Attendance::query()
            ->where('mahasiswa_id', $mahasiswa->id)
            ->pluck('event_id');

        return static::query()->where('status', 'aktif')->whereNotIn('id', $attendedEventIds)->first()
            ?? static::query()->where('status', 'aktif')->first();
    }
}
