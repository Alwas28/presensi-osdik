<?php

use App\Exceptions\PresensiException;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\Mahasiswa;
use App\Support\AttendanceRecorder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.mahasiswa', ['title' => 'Presensi'])] class extends Component
{
    public string $method = 'scan';

    public ?string $feedback = null;

    public ?string $feedbackType = null;

    public ?string $selfQrPayload = null;

    #[Computed]
    public function mahasiswa(): Mahasiswa
    {
        return auth()->user()->mahasiswa;
    }

    #[Computed]
    public function activeEvent(): ?Event
    {
        return Event::currentFor($this->mahasiswa);
    }

    #[Computed]
    public function attendanceForActive(): ?Attendance
    {
        if (! $this->activeEvent) {
            return null;
        }

        return Attendance::query()
            ->where('mahasiswa_id', $this->mahasiswa->id)
            ->where('event_id', $this->activeEvent->id)
            ->first();
    }

    /**
     * Regenerates the student's self-QR token. Kept as a plain property refreshed
     * by an explicit action (mirroring Admin\QrModal::ensureFreshToken()) instead of
     * a #[Computed] read only from Alpine — a Computed value never touched from PHP
     * isn't included in Livewire's normal response payload, so wire:poll wasn't
     * reliably pushing a fresh token to the displayed QR image.
     */
    public function refreshSelfQr(): void
    {
        $this->selfQrPayload = $this->activeEvent
            ? app(AttendanceRecorder::class)->buildSelfQrPayload($this->mahasiswa, $this->activeEvent)
            : null;
    }

    public function setMethod(string $method): void
    {
        $this->method = $method;
        $this->feedback = null;
        $this->feedbackType = null;

        if ($method === 'show') {
            $this->refreshSelfQr();
        }
    }

    public function checkinWithPayload(string $payload): void
    {
        try {
            $attendance = app(AttendanceRecorder::class)->recordFromEventQr($this->mahasiswa, $payload, $this->activeEvent?->id);
            $this->feedbackType = 'success';
            $this->feedback = 'Presensi berhasil dicatat pukul '.$attendance->check_in->format('H:i').'.';
            $this->dispatch('presensi-berhasil', message: $this->feedback);
        } catch (PresensiException $e) {
            $this->feedbackType = 'error';
            $this->feedback = $e->getMessage();
        }
    }

    /**
     * Fallback for when scanning the QR just isn't working for this student —
     * only available when the panitia has switched it on for the active event.
     */
    public function manualCheckin(): void
    {
        if (! $this->activeEvent) {
            $this->feedbackType = 'error';
            $this->feedback = 'Kegiatan tidak ditemukan.';

            return;
        }

        try {
            $attendance = app(AttendanceRecorder::class)->recordManualCheckin($this->mahasiswa, $this->activeEvent);
            $this->feedbackType = 'success';
            $this->feedback = 'Presensi manual berhasil dicatat pukul '.$attendance->check_in->format('H:i').'.';
            $this->dispatch('presensi-berhasil', message: $this->feedback);
        } catch (PresensiException $e) {
            $this->feedbackType = 'error';
            $this->feedback = $e->getMessage();
        }
    }
}; ?>

<div class="p-5 pb-2">
    <h2 class="display font-bold text-base mb-4">Presensi</h2>

    @if (! $this->activeEvent)
        <div class="flex flex-col items-center text-center py-8">
            <div class="flex items-center justify-center rounded-full mb-4" style="width:56px;height:56px;background:#fdf3d8;"><i class="ti ti-lock" style="font-size:26px; color:#8a6a00;"></i></div>
            <p class="display font-bold text-sm mb-1">Belum ada presensi dibuka</p>
            <p class="text-xs" style="color:var(--ink-soft);">Tunggu panitia membuka presensi kegiatan.</p>
        </div>
    @elseif ($this->attendanceForActive)
        <div class="flex flex-col items-center text-center py-8">
            <div class="flex items-center justify-center rounded-full mb-4" style="width:56px;height:56px;background:#e7f3ea;"><i class="ti ti-circle-check" style="font-size:28px; color:var(--umk-green);"></i></div>
            <p class="display font-bold text-sm mb-1">Sudah presensi</p>
            <p class="text-xs" style="color:var(--ink-soft);">Kamu tercatat hadir pukul {{ $this->attendanceForActive->check_in->format('H:i') }}.</p>
        </div>
    @else
        <div x-data="presensiPanel()" wire:key="panel-{{ $this->activeEvent->id }}">
            <div class="flex gap-2 mb-4">
                <button class="chip {{ $method === 'scan' ? 'active' : '' }}" style="flex:1; text-align:center;" wire:click="setMethod('scan')" @click="stopCamera()">Scan QR panitia</button>
                <button class="chip {{ $method === 'show' ? 'active' : '' }}" style="flex:1; text-align:center;" wire:click="setMethod('show')" @click="stopCamera()">Tunjukkan QR saya</button>
            </div>

            @if ($feedback)
                <div class="p-3 rounded-lg text-xs font-medium mb-4" style="{{ $feedbackType === 'success' ? 'background:#e7f3ea; color:var(--umk-green);' : 'background:#fdeaea; color:var(--umk-red);' }}">
                    {{ $feedback }}
                </div>
            @endif

            <div x-show="!checking">
                @if ($method === 'scan')
                    <div class="viewfinder mb-4" x-init="startCamera()">
                        <div id="qr-reader" style="width:100%; height:100%;"></div>
                        <template x-if="!cameraOn">
                            <div>
                                <div class="vf-corner" style="top:14px; left:14px; border-top:3px solid var(--umk-gold); border-left:3px solid var(--umk-gold); border-radius:8px 0 0 0;"></div>
                                <div class="vf-corner" style="top:14px; right:14px; border-top:3px solid var(--umk-gold); border-right:3px solid var(--umk-gold); border-radius:0 8px 0 0;"></div>
                                <div class="vf-corner" style="bottom:14px; left:14px; border-bottom:3px solid var(--umk-gold); border-left:3px solid var(--umk-gold); border-radius:0 0 0 8px;"></div>
                                <div class="vf-corner" style="bottom:14px; right:14px; border-bottom:3px solid var(--umk-gold); border-right:3px solid var(--umk-gold); border-radius:0 0 8px 0;"></div>
                                <div class="absolute inset-0 flex items-center justify-center" style="pointer-events:none;"><p class="text-xs" style="color:#7c9c85;">Arahkan kamera ke QR Code</p></div>
                            </div>
                        </template>
                    </div>
                    <template x-if="cameraError">
                        <p class="text-xs text-center mb-3" style="color:var(--umk-red);">
                            Kamera tidak bisa diakses. Pastikan izin kamera sudah diberikan di pengaturan browser{{ $this->activeEvent->izinkan_presensi_manual ? ', atau gunakan Presensi Manual di bawah.' : '.' }}
                        </p>
                    </template>
                    <button class="btn btn-primary w-full mb-3" @click="cameraOn ? stopCamera() : startCamera()" :disabled="starting">
                        <span x-text="starting ? 'Meminta izin kamera...' : (cameraOn ? 'Berhenti scan' : 'Mulai scan')"></span>
                    </button>
                    @if ($this->activeEvent->izinkan_presensi_manual)
                        <button type="button" class="btn btn-outline w-full" wire:click="manualCheckin">
                            <i class="ti ti-hand-click"></i>Presensi Manual
                        </button>
                    @endif
                @else
                    <div class="flex flex-col items-center text-center">
                        <div class="flex items-center justify-center p-4 mb-3" style="background:#fff; border:1px solid var(--line); border-radius:12px;"
                             wire:ignore wire:poll.20s="refreshSelfQr"
                             x-effect="renderSelfQr($refs.selfQrWrap, $wire.selfQrPayload)">
                            <div x-ref="selfQrWrap"></div>
                        </div>
                        <p class="text-xs" style="color:var(--ink-soft);">Tunjukkan QR ini ke panitia untuk dipindai.</p>
                    </div>
                @endif
            </div>

            <div x-show="checking" x-cloak class="py-3">
                <template x-for="(step, idx) in steps" :key="idx">
                    <div class="checklist-item" :class="idx < stepIndex ? 'done' : (idx === stepIndex && stepFailed ? 'fail' : '')">
                        <div class="checklist-dot">
                            <i class="ti ti-check" x-show="idx < stepIndex"></i>
                            <i class="ti ti-x" x-show="idx === stepIndex && stepFailed"></i>
                        </div>
                        <span x-text="step"></span>
                    </div>
                </template>
            </div>
        </div>
    @endif
</div>

@script
<script>
    window.renderSelfQr = window.renderSelfQr || function (wrap, payload) {
        if (!wrap || !payload) return;
        wrap.innerHTML = '';
        // Size the QR off the actual available width instead of a fixed value, so it
        // fills as much of the screen as it safely can on any phone (and stays capped
        // on the desktop-width preview, since .ms-app itself maxes out at 480px).
        const containerWidth = document.querySelector('.ms-app')?.clientWidth || window.innerWidth;
        const size = Math.min(340, Math.max(240, containerWidth - 80));
        new QRCode(wrap, { text: payload, width: size, height: size, colorDark: '#122016', colorLight: '#ffffff' });
    };

    Alpine.data('presensiPanel', () => ({
        cameraOn: false,
        cameraError: false,
        starting: false,
        checking: false,
        stepFailed: false,
        stepIndex: -1,
        steps: ['Memindai QR Code', 'Memvalidasi token', 'Memeriksa kegiatan aktif', 'Memeriksa duplikasi presensi', 'Menyimpan presensi'],
        html5Qr: null,

        startCamera() {
            if (this.cameraOn || this.starting) return;
            if (typeof window.Html5Qrcode === 'undefined') {
                this.cameraError = true;
                return;
            }
            this.starting = true;
            this.cameraError = false;
            this.$nextTick(() => {
                this.html5Qr = new Html5Qrcode('qr-reader');
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
                    this.stopCamera();
                    this.runCheckin(decodedText);
                };
                const onFinished = () => {
                    this.cameraOn = true;
                    this.starting = false;
                };
                const onFailed = (err) => {
                    // Permission denied, no camera available, or insecure (non-HTTPS/localhost) context.
                    console.error('[presensi] camera start failed:', err);
                    this.cameraOn = false;
                    this.starting = false;
                    this.cameraError = true;
                };

                this.html5Qr.start({ facingMode: 'environment' }, config, onScan, () => {})
                    .then(onFinished)
                    .catch(() => {
                        // Some devices have no rear-facing camera — fall back to whatever
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
        stopCamera() {
            this.cameraOn = false;
            this.starting = false;
            if (this.html5Qr) {
                const instance = this.html5Qr;
                this.html5Qr = null;
                instance.stop().then(() => instance.clear()).catch(() => {});
            }
        },
        destroy() {
            this.stopCamera();
        },
        async runCheckin(payload) {
            this.checking = true;
            this.stepFailed = false;
            this.stepIndex = 0;
            for (let i = 1; i < this.steps.length - 1; i++) {
                await new Promise((r) => setTimeout(r, 350));
                this.stepIndex = i;
            }
            await this.$wire.checkinWithPayload(payload);
            this.stepIndex = this.steps.length - 1;
            this.stepFailed = this.$wire.feedbackType === 'error';
            await new Promise((r) => setTimeout(r, 500));
            this.checking = false;
            this.stepIndex = -1;
        },
    }));
</script>
@endscript
