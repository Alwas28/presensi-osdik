<?php

use App\Exceptions\PresensiException;
use App\Support\AttendanceRecorder;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.admin', ['title' => 'Scan presensi'])] class extends Component
{
    /** @var list<array{name: string, time: string, success: bool, message: string}> */
    public array $log = [];

    public function scan(string $payload): void
    {
        try {
            $attendance = app(AttendanceRecorder::class)->recordFromSelfQr($payload)->load('mahasiswa', 'event');

            array_unshift($this->log, [
                'name' => $attendance->mahasiswa->nama,
                'time' => $attendance->check_in->format('H:i:s'),
                'success' => true,
                'message' => 'Hadir — '.$attendance->event->nama,
            ]);
        } catch (PresensiException $e) {
            array_unshift($this->log, [
                'name' => '-',
                'time' => now()->format('H:i:s'),
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }

        $this->log = array_slice($this->log, 0, 20);
    }
}; ?>

<div>
    <p class="text-sm mb-4" style="color:var(--ink-soft);">Arahkan kamera ke QR yang ditampilkan di aplikasi mahasiswa (menu Presensi &rarr; Tunjukkan QR saya). Kegiatan diambil otomatis dari QR, tidak perlu memilih manual.</p>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="card p-4" x-data="adminScanner()">
            <div class="viewfinder mb-3" style="max-width:360px; margin:0 auto;">
                <div id="admin-qr-reader" style="width:100%; height:100%;"></div>
            </div>
            <button class="btn btn-primary w-full justify-center" @click="cameraOn ? stop() : start()">
                <span x-text="cameraOn ? 'Berhenti scan' : 'Mulai scan'"></span>
            </button>
        </div>

        <div class="card overflow-hidden">
            <div class="p-4 pb-0"><h3 class="font-semibold text-sm">Hasil scan terbaru</h3></div>
            <div class="overflow-x-auto mt-2">
                <table>
                    <thead><tr><th>Waktu</th><th>Nama</th><th>Status</th><th>Keterangan</th></tr></thead>
                    <tbody>
                        @forelse ($log as $entry)
                            <tr>
                                <td>{{ $entry['time'] }}</td>
                                <td>{{ $entry['name'] }}</td>
                                <td><span class="pill {{ $entry['success'] ? 'pill-active' : 'pill-closed' }}">{{ $entry['success'] ? 'Berhasil' : 'Gagal' }}</span></td>
                                <td>{{ $entry['message'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="color:var(--ink-soft);">Belum ada hasil scan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@script
<script>
    Alpine.data('adminScanner', () => ({
        cameraOn: false,
        html5Qr: null,
        busy: false,

        start() {
            this.cameraOn = true;
            this.$nextTick(() => {
                this.html5Qr = new Html5Qrcode('admin-qr-reader');
                this.html5Qr.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: 240 },
                    (decodedText) => {
                        if (this.busy) return;
                        this.busy = true;
                        this.$wire.scan(decodedText).finally(() => {
                            setTimeout(() => { this.busy = false; }, 1200);
                        });
                    },
                    () => {}
                ).catch(() => {
                    this.cameraOn = false;
                });
            });
        },
        stop() {
            if (this.html5Qr) {
                this.html5Qr.stop().then(() => this.html5Qr.clear()).catch(() => {});
                this.html5Qr = null;
            }
            this.cameraOn = false;
        },
    }));
</script>
@endscript
