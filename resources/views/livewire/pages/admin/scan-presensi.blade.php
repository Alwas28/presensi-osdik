<?php

use App\Exceptions\PresensiException;
use App\Models\Event;
use App\Support\AttendanceRecorder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.admin', ['title' => 'Scan presensi'])] class extends Component
{
    public string $tab = 'scan';

    /** @var list<array{name: string, time: string, success: bool, message: string}> */
    public array $log = [];

    public ?string $eventQrPayload = null;

    #[Computed]
    public function activeEvent(): ?Event
    {
        return Event::query()->where('status', 'aktif')->first();
    }

    #[Computed]
    public function remainingSeconds(): int
    {
        $event = $this->activeEvent;

        if (! $event?->token_generated_at) {
            return 0;
        }

        return max(0, 60 - $event->token_generated_at->diffInSeconds(now(), true));
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;

        if ($tab === 'qr') {
            $this->refreshEventQr();
        }
    }

    /**
     * Mirrors Admin\QrModal::ensureFreshToken() — kept as a plain property
     * refreshed by an explicit action instead of a #[Computed] read only from
     * Alpine, since Livewire won't reliably push an untouched Computed value to
     * the client on every wire:poll tick.
     */
    public function refreshEventQr(): void
    {
        $event = $this->activeEvent;

        if (! $event) {
            $this->eventQrPayload = null;

            return;
        }

        $remaining = $event->token_generated_at
            ? max(0, 60 - $event->token_generated_at->diffInSeconds(now(), true))
            : 0;

        if (! $event->current_token || $remaining <= 0) {
            $event->rotateToken();
        }

        $this->eventQrPayload = "EVT:{$event->id}:{$event->current_token}";
    }

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

            $this->dispatch('presensi-panitia-berhasil', message: $attendance->mahasiswa->nama.' — presensi berhasil dicatat.');
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
    <div class="flex gap-2 mb-4" style="max-width:360px;">
        <button class="chip {{ $tab === 'scan' ? 'active' : '' }}" style="flex:1; text-align:center;" wire:click="setTab('scan')">Scan QR Mahasiswa</button>
        <button class="chip {{ $tab === 'qr' ? 'active' : '' }}" style="flex:1; text-align:center;" wire:click="setTab('qr')">QR Kegiatan</button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @if ($tab === 'scan')
            <div class="card p-4" x-data="adminScanner()" x-init="start()">
                <p class="text-sm mb-3" style="color:var(--ink-soft);">Arahkan kamera ke QR yang ditampilkan di aplikasi mahasiswa (menu Presensi &rarr; Tunjukkan QR saya). Kegiatan diambil otomatis dari QR, tidak perlu memilih manual.</p>
                <div class="viewfinder mb-3" style="max-width:360px; margin:0 auto; position:relative;">
                    <div id="admin-qr-reader" style="width:100%; height:100%;"></div>
                    <div :style="`position:absolute; inset:0; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:16px; border-radius:inherit; display:${resultOverlay ? 'flex' : 'none'}; background:${resultOverlay && resultOverlay.success ? 'rgba(18,32,22,0.92)' : 'rgba(48,10,10,0.92)'};`">
                        <i class="ti" :class="resultOverlay && resultOverlay.success ? 'ti-circle-check' : 'ti-circle-x'" style="font-size:48px; color:#fff;"></i>
                        <p class="font-semibold text-sm mt-2" style="color:#fff;" x-text="resultOverlay ? resultOverlay.text : ''"></p>
                    </div>
                </div>
                <template x-if="cameraError">
                    <p class="text-xs text-center mb-3" style="color:var(--umk-red);">Kamera tidak bisa diakses. Pastikan browser sudah diberi izin kamera (dan halaman dibuka lewat HTTPS atau localhost), lalu coba lagi.</p>
                </template>
                <button class="btn btn-primary w-full justify-center" @click="cameraOn ? stop() : start()" :disabled="starting">
                    <span x-text="starting ? 'Meminta izin kamera...' : (cameraOn ? 'Berhenti scan' : 'Mulai scan')"></span>
                </button>
            </div>
        @else
            <div class="card p-4 flex flex-col items-center text-center">
                @if ($this->activeEvent)
                    <p class="text-sm font-semibold mb-1">{{ $this->activeEvent->nama }}</p>
                    <p class="text-xs mb-3" style="color:var(--ink-soft);">Minta mahasiswa memindai QR ini lewat menu Presensi &rarr; Scan QR panitia.</p>
                    <div class="flex items-center justify-center p-4 mb-3" style="background:#fff; border:1px solid var(--line); border-radius:12px;"
                         wire:ignore wire:poll.1000ms="refreshEventQr"
                         x-data
                         x-effect="renderEventQr($refs.eventQrWrap, $wire.eventQrPayload)">
                        <div x-ref="eventQrWrap"></div>
                    </div>
                    <p class="text-xs" style="color:var(--ink-soft);">Token diperbarui dalam <span class="font-semibold" style="color:var(--umk-green);">{{ $this->remainingSeconds }}</span> detik</p>
                @else
                    <div class="flex items-center justify-center rounded-full mb-4" style="width:56px;height:56px;background:#fdf3d8;"><i class="ti ti-lock" style="font-size:26px; color:#8a6a00;"></i></div>
                    <p class="font-semibold text-sm mb-1">Belum ada kegiatan yang dibuka</p>
                    <p class="text-xs" style="color:var(--ink-soft);">Buka presensi kegiatan terlebih dahulu di menu Kegiatan.</p>
                @endif
            </div>
        @endif

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
    window.renderEventQr = window.renderEventQr || function (wrap, payload) {
        if (!wrap || !payload) return;
        wrap.innerHTML = '';
        new QRCode(wrap, { text: payload, width: 220, height: 220, colorDark: '#122016', colorLight: '#ffffff' });
    };

    Alpine.data('adminScanner', () => ({
        cameraOn: false,
        cameraError: false,
        starting: false,
        html5Qr: null,
        busy: false,
        resultOverlay: null,
        lastPayload: null,

        start() {
            if (this.cameraOn || this.starting) return;
            if (typeof window.Html5Qrcode === 'undefined') {
                this.cameraError = true;
                return;
            }
            this.starting = true;
            this.cameraError = false;
            this.$nextTick(() => {
                this.html5Qr = new Html5Qrcode('admin-qr-reader');
                const config = {
                    fps: 10,
                    // A fixed pixel qrbox can exceed the actual camera viewfinder on some
                    // devices, silently preventing any code from ever being detected.
                    // Size it relative to the real viewfinder instead.
                    qrbox: (viewfinderWidth, viewfinderHeight) => {
                        const edge = Math.floor(Math.min(viewfinderWidth, viewfinderHeight) * 0.7);

                        return { width: edge, height: edge };
                    },
                };
                const onScan = (decodedText) => {
                    if (this.busy) return;

                    // A self-QR is one-time-use: once it's been scanned, the mahasiswa's
                    // screen keeps showing the exact same image for a while (until the next
                    // rotation), so the camera will keep re-detecting it after resuming.
                    // Ignore repeats of a code we already processed instead of hitting the
                    // server again and surfacing a scary "kedaluwarsa" error for something
                    // that actually already succeeded.
                    if (decodedText === this.lastPayload) return;

                    this.busy = true;
                    this.lastPayload = decodedText;

                    // Freeze the camera on the current frame so the still-visible QR
                    // can't be decoded and processed again while the result is shown.
                    if (this.html5Qr) {
                        try { this.html5Qr.pause(true); } catch (e) {}
                    }

                    this.$wire.scan(decodedText).then(() => {
                        const entry = (this.$wire.log || [])[0] || null;
                        this.resultOverlay = entry
                            ? { success: entry.success, text: entry.success ? (entry.name + ' — presensi berhasil dicatat.') : entry.message }
                            : null;
                    }).finally(() => {
                        setTimeout(() => {
                            this.resultOverlay = null;
                            this.busy = false;
                            if (this.cameraOn && this.html5Qr) {
                                try { this.html5Qr.resume(); } catch (e) {}
                            }
                        }, 2000);
                    });
                };
                const onFinished = () => {
                    this.cameraOn = true;
                    this.starting = false;
                };
                const onFailed = (err) => {
                    console.error('[scan-presensi] camera start failed:', err);
                    this.cameraOn = false;
                    this.starting = false;
                    this.cameraError = true;
                };

                this.html5Qr.start({ facingMode: 'environment' }, config, onScan, () => {})
                    .then(onFinished)
                    .catch(() => {
                        // Many laptops/desktops have no rear-facing camera, which makes
                        // facingMode:'environment' fail outright — fall back to whatever
                        // camera the browser can actually offer.
                        Html5Qrcode.getCameras()
                            .then((cameras) => {
                                if (!cameras || !cameras.length) throw new Error('Tidak ada kamera terdeteksi.');

                                return this.html5Qr.start(cameras[0].id, config, onScan, () => {});
                            })
                            .then(onFinished)
                            .catch(onFailed);
                    });
            });
        },
        stop() {
            this.cameraOn = false;
            this.starting = false;
            this.busy = false;
            this.resultOverlay = null;
            if (this.html5Qr) {
                const instance = this.html5Qr;
                this.html5Qr = null;
                instance.stop().then(() => instance.clear()).catch(() => {});
            }
        },
        destroy() {
            this.stop();
        },
    }));
</script>
@endscript
