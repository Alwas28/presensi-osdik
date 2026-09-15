<?php

namespace App\Support;

use App\Exceptions\PresensiException;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\Mahasiswa;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

/**
 * Centralizes the presensi validation rules from the blueprint (token valid?
 * event active? already attended?) so every check-in path — the student
 * scanning the panitia's QR, typing its token manually, or the panitia
 * scanning the student's own QR — enforces the exact same rules.
 */
class AttendanceRecorder
{
    private const SELF_QR_CACHE_PREFIX = 'self-qr:';

    /**
     * Record attendance from the event's QR/token, shown on the panitia's screen.
     *
     * @param  string  $payload  Either "EVT:{eventId}:{token}" (camera scan) or a bare token (manual entry).
     *
     * @throws PresensiException
     */
    public function recordFromEventQr(Mahasiswa $mahasiswa, string $payload, ?int $selectedEventId = null): Attendance
    {
        [$eventId, $token] = $this->parseEventPayload($payload, $selectedEventId);

        $event = Event::find($eventId);

        if (! $event) {
            throw new PresensiException('Kegiatan tidak ditemukan.');
        }

        if ($event->status !== 'aktif') {
            throw new PresensiException('Kegiatan belum dibuka atau sudah ditutup untuk presensi.');
        }

        if (! $event->hasValidToken($token)) {
            throw new PresensiException('Token QR tidak valid atau sudah kedaluwarsa, coba lagi.');
        }

        return $this->createAttendance($mahasiswa, $event);
    }

    /**
     * Record attendance from a student's self-displayed QR, scanned by the panitia.
     *
     * @param  string  $payload  "SELF:{shortToken}" produced by buildSelfQrPayload().
     *
     * @throws PresensiException
     */
    public function recordFromSelfQr(string $payload): Attendance
    {
        if (! str_starts_with($payload, 'SELF:')) {
            throw new PresensiException('QR ini bukan QR presensi mahasiswa.');
        }

        $token = substr($payload, 5);
        $cacheKey = self::SELF_QR_CACHE_PREFIX.$token;
        $decoded = Cache::get($cacheKey);

        if (! $decoded) {
            throw new PresensiException('QR sudah kedaluwarsa atau tidak valid, minta mahasiswa membuka ulang halaman presensi.');
        }

        // One-time use: once scanned, this exact QR frame can't be replayed.
        Cache::forget($cacheKey);

        $mahasiswa = Mahasiswa::find($decoded['mahasiswa_id'] ?? null);
        $event = Event::find($decoded['event_id'] ?? null);

        if (! $mahasiswa || ! $event) {
            throw new PresensiException('Data mahasiswa atau kegiatan tidak ditemukan.');
        }

        if ($event->status !== 'aktif') {
            throw new PresensiException('Kegiatan belum dibuka atau sudah ditutup untuk presensi.');
        }

        return $this->createAttendance($mahasiswa, $event);
    }

    /**
     * Build the short-lived, opaque payload a student's app displays as their
     * own QR code for a panitia to scan. Kept short (a random token, not an
     * encrypted blob) so the QR stays low-density and easy to scan phone-to-phone.
     */
    public function buildSelfQrPayload(Mahasiswa $mahasiswa, Event $event, int $ttlSeconds = 90): string
    {
        $token = Str::random(40);

        Cache::put(self::SELF_QR_CACHE_PREFIX.$token, [
            'mahasiswa_id' => $mahasiswa->id,
            'event_id' => $event->id,
        ], $ttlSeconds);

        return 'SELF:'.$token;
    }

    /**
     * @return array{0: int, 1: string} [eventId, token]
     *
     * @throws PresensiException
     */
    private function parseEventPayload(string $payload, ?int $selectedEventId): array
    {
        if (str_starts_with($payload, 'EVT:')) {
            $parts = explode(':', $payload, 3);

            if (count($parts) !== 3 || $parts[1] === '' || $parts[2] === '') {
                throw new PresensiException('QR tidak valid.');
            }

            return [(int) $parts[1], $parts[2]];
        }

        if (! $selectedEventId) {
            throw new PresensiException('Pilih kegiatan terlebih dahulu.');
        }

        return [$selectedEventId, $payload];
    }

    /**
     * @throws PresensiException
     */
    private function createAttendance(Mahasiswa $mahasiswa, Event $event): Attendance
    {
        if (Attendance::query()->where('mahasiswa_id', $mahasiswa->id)->where('event_id', $event->id)->exists()) {
            throw new PresensiException('Kamu sudah presensi untuk kegiatan ini.');
        }

        try {
            return Attendance::query()->create([
                'mahasiswa_id' => $mahasiswa->id,
                'event_id' => $event->id,
                'attendance_date' => now()->toDateString(),
                'check_in' => now(),
                'ip_address' => Request::ip(),
                'device' => Request::userAgent(),
                'status' => 'hadir',
            ]);
        } catch (QueryException) {
            throw new PresensiException('Kamu sudah presensi untuk kegiatan ini.');
        }
    }
}
